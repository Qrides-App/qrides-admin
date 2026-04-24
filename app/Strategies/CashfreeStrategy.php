<?php

namespace App\Strategies;

use App\Http\Controllers\Traits\MiscellaneousTrait;
use App\Http\Controllers\Traits\PaymentStatusUpdaterTrait;
use App\Models\AppUser;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;

class CashfreeStrategy implements PaymentStrategy
{
    use MiscellaneousTrait, PaymentStatusUpdaterTrait;

    private string $appId;
    private string $secretKey;
    private string $baseUrl;

    public function __construct()
    {
        $mode = $this->getGeneralSettingValue('cashfree_options') === 'live' ? 'live' : 'test';
        $this->appId = $this->getGeneralSettingValue($mode === 'live' ? 'live_cashfree_app_id' : 'test_cashfree_app_id');
        $this->secretKey = $this->getGeneralSettingValue($mode === 'live' ? 'live_cashfree_secret_key' : 'test_cashfree_secret_key');
        $this->baseUrl = $mode === 'live' ? 'https://api.cashfree.com/pg' : 'https://sandbox.cashfree.com/pg';
    }

    public function process($bookingId, $bookingData, $request)
    {
        $orderId = 'booking_'.$bookingId.'_'.time();
        $payload = [
            'order_id' => $orderId,
            'order_amount' => (float) $bookingData->amount_to_pay,
            'order_currency' => $bookingData->currency_code,
            'customer_details' => [
                'customer_id' => $bookingData->user_id ?? 'user_'.$bookingData->id,
                'customer_phone' => '',
                'customer_email' => '',
            ],
            'order_meta' => [
                'return_url' => url('/payment/return?booking='.$bookingId.'&method=cashfree&order_id='.$orderId),
            ],
        ];

        $response = $this->createOrder($payload);
        if ($response['status'] !== 'success') {
            return redirect('/invalid-order')->with('error', $response['message'] ?? 'Cashfree order failed.');
        }

        $data = $response['data'];
        return $this->renderHostedCheckout($data, 'Unable to start Cashfree checkout for this booking.');
    }

    public function return($bookingId, $request)
    {
        $verify = $this->verifySignatureFromReturn($request);
        if ($verify !== true) {
            return '/payment_fail';
        }

        $status = $request['order_status'] ?? '';
        if ($status === 'PAID') {
            $transactionData = new \stdClass;
            $transactionData->response_data = json_encode($request);
            $transactionData->gateway_name = 'cashfree';
            $transactionData->payment_status = 'completed';
            $transactionData->transaction_id = $request['cf_payment_id'] ?? '';
            $this->updateBookingStatus($bookingId, $transactionData);
            return '/payment_success';
        }
        return '/payment_fail';
    }

    public function callback($bookingId, $request)
    {
        return $this->return($bookingId, $request);
    }

    public function rechargeWallet($userID, $amount, $currency, $request)
    {
        $userToken = $request->input('userToken');
        $driver = AppUser::find($userID);
        $orderId = 'recharge_'.$userID.'_'.time();
        $customerPhone = trim((string) (($driver->phone_country ?? '').($driver->phone ?? '')));
        $customerEmail = trim((string) ($driver->email ?? ''));
        $payload = [
            'order_id' => $orderId,
            'order_amount' => (float) $amount,
            'order_currency' => $currency,
            'customer_details' => [
                'customer_id' => 'driver_'.$userID,
                'customer_phone' => $customerPhone !== '' ? $customerPhone : '9999999999',
                'customer_email' => $customerEmail !== '' ? $customerEmail : 'driver_'.$userID.'@qrides.in',
            ],
            'order_meta' => [
                'return_url' => route('wallet_recharge_return', [
                    'booking' => $orderId,
                    'method' => 'cashfree',
                    'token' => $userToken,
                    'plan_id' => $request->input('plan_id'),
                    'duration_days' => $request->input('duration_days'),
                ]),
            ],
        ];
        $response = $this->createOrder($payload);
        if ($response['status'] !== 'success') {
            Log::error('Cashfree recharge order failed', [
                'user_id' => $userID,
                'payload' => $payload,
                'response' => $response['message'] ?? 'Cashfree order failed.',
            ]);
            return redirect('/invalid-order')->with('error', $response['message'] ?? 'Cashfree order failed.');
        }

        return $this->renderHostedCheckout(
            $response['data'],
            'Unable to start Cashfree checkout for this recharge.'
        );
    }

    public function refund($bookingId, $bookingData) {}

    private function createOrder(array $payload): array
    {
        $resp = Http::withHeaders([
            'x-client-id' => $this->appId,
            'x-client-secret' => $this->secretKey,
            'x-api-version' => '2023-08-01',
        ])->post($this->baseUrl.'/orders', $payload);

        if (! $resp->ok()) {
            return ['status' => 'error', 'message' => $resp->body()];
        }

        $data = $resp->json();
        if (isset($data['order_status']) && $data['order_status'] === 'ACTIVE') {
            return ['status' => 'success', 'data' => $data];
        }

        return ['status' => 'error', 'message' => $resp->body()];
    }

    private function renderHostedCheckout(array $data, string $fallbackMessage)
    {
        if (! empty($data['payment_link'])) {
            return redirect($data['payment_link']);
        }

        $paymentSessionId = $data['payment_session_id'] ?? null;
        if (empty($paymentSessionId)) {
            Log::error('Cashfree checkout payload missing payment_session_id', [
                'payload' => $data,
            ]);

            return redirect('/invalid-order')->with('error', $fallbackMessage);
        }

        $checkoutAction = str_contains($this->baseUrl, 'sandbox')
            ? 'https://sandbox.cashfree.com/pg/view/sessions/checkout'
            : 'https://api.cashfree.com/pg/view/sessions/checkout';

        $paymentSessionId = e((string) $paymentSessionId);
        $checkoutAction = e($checkoutAction);

        $html = new HtmlString(<<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting to Cashfree</title>
</head>
<body style="font-family: Arial, sans-serif; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0;">
    <form id="cashfree-checkout-form" action="{$checkoutAction}" method="post">
        <input type="hidden" name="payment_session_id" value="{$paymentSessionId}">
    </form>
    <p style="color:#374151; font-size:16px;">Redirecting to secure payment page...</p>
    <script>
        (function () {
            const form = document.getElementById('cashfree-checkout-form');
            const meta = { userAgent: window.navigator.userAgent };
            const sortedMeta = Object.entries(meta)
                .sort(([a], [b]) => a.localeCompare(b))
                .reduce((acc, [key, value]) => {
                    acc[key] = value;
                    return acc;
                }, {});
            const browserMeta = document.createElement('input');
            browserMeta.type = 'hidden';
            browserMeta.name = 'browser_meta';
            browserMeta.value = btoa(JSON.stringify(sortedMeta));
            form.appendChild(browserMeta);
            form.submit();
        })();
    </script>
</body>
</html>
HTML);

        return response($html, 200)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    private function verifySignatureFromReturn($request)
    {
        $payload = $request->all();
        $signature = $payload['signature'] ?? '';
        $orderId = $payload['order_id'] ?? '';
        if ($signature === '' || $orderId === '') {
            return false;
        }
        $computed = base64_encode(hash_hmac('sha256', $orderId, $this->secretKey, true));
        return hash_equals($computed, $signature);
    }

    public function cancel($bookingId, $bookingData)
    {
        return '/payment_methods?booking='.$bookingId;
    }
}
