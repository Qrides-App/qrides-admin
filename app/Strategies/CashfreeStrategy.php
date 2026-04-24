<?php

namespace App\Strategies;

use App\Http\Controllers\Traits\MiscellaneousTrait;
use App\Http\Controllers\Traits\PaymentStatusUpdaterTrait;
use App\Models\AppUser;
use App\Models\DriverRechargePlan;
use App\Models\GeneralSetting;
use App\Services\RechargeBillingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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
        $payload = $this->normalizePayload($request);
        $orderId = $payload['order_id'] ?? $payload['cf_order_id'] ?? $bookingId;
        $isWalletRecharge = str_starts_with((string) $bookingId, 'recharge_') || ! empty($payload['token']);
        $orderData = $this->fetchOrderDetails($orderId);

        if ($orderData === null) {
            return $isWalletRecharge
                ? route('wallet_recharge_fail', ['userToken' => $payload['token'] ?? null])
                : '/payment_fail';
        }

        $status = strtoupper((string) ($orderData['order_status'] ?? $payload['order_status'] ?? ''));
        if ($status === 'PAID') {
            if ($isWalletRecharge) {
                return $this->handleRechargePaymentStatus($payload, $orderData, $orderId);
            }

            $transactionData = new \stdClass;
            $transactionData->response_data = json_encode([
                'return_payload' => $payload,
                'order' => $orderData,
            ]);
            $transactionData->gateway_name = 'cashfree';
            $transactionData->payment_status = 'completed';
            $transactionData->transaction_id = $orderData['cf_order_id'] ?? $orderId;
            $this->updateBookingStatus($bookingId, $transactionData);
            return '/payment_success';
        }

        return $isWalletRecharge
            ? route('wallet_recharge_fail', ['userToken' => $payload['token'] ?? null])
            : '/payment_fail';
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

    private function normalizePayload($request): array
    {
        if (is_array($request)) {
            return $request;
        }

        if (is_object($request) && method_exists($request, 'all')) {
            return $request->all();
        }

        return [];
    }

    private function verifySignatureFromReturn($request)
    {
        $payload = $this->normalizePayload($request);
        $signature = $payload['signature'] ?? '';
        $orderId = $payload['order_id'] ?? '';
        if ($signature === '' || $orderId === '') {
            return false;
        }
        $computed = base64_encode(hash_hmac('sha256', $orderId, $this->secretKey, true));
        return hash_equals($computed, $signature);
    }

    private function fetchOrderDetails(string $orderId): ?array
    {
        if ($orderId === '') {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'x-client-id' => $this->appId,
                'x-client-secret' => $this->secretKey,
                'x-api-version' => '2023-08-01',
            ])->get($this->baseUrl.'/orders/'.rawurlencode($orderId));

            if (! $response->ok()) {
                Log::error('Cashfree order lookup failed', [
                    'order_id' => $orderId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('Cashfree order lookup exception', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function handleRechargePaymentStatus(array $request, array $orderData, string $orderId)
    {
        $userToken = $request['token'] ?? null;
        if (! $userToken) {
            return route('wallet_recharge_fail');
        }

        $driverId = $this->checkUserByToken($userToken);
        if (! $driverId) {
            return route('wallet_recharge_fail', ['userToken' => $userToken]);
        }

        $transactionId = $orderData['cf_order_id'] ?? $orderId;

        try {
            DB::transaction(function () use ($driverId, $request, $transactionId, $orderId) {
                $walletColumns = $this->getVendorWalletColumns();
                $billing = new RechargeBillingService();
                $driverLocked = AppUser::where('id', $driverId)
                    ->where('user_type', 'driver')
                    ->lockForUpdate()
                    ->firstOrFail();

                $existingTransactionQuery = DB::table('vendor_wallets')
                    ->where('vendor_id', $driverLocked->id);

                if (isset($walletColumns['payment_method'])) {
                    $existingTransactionQuery->where('payment_method', 'cashfree');
                }

                if (isset($walletColumns['txn_id'])) {
                    $existingTransactionQuery->where('txn_id', $transactionId);
                } else {
                    $existingTransactionQuery->where('description', 'like', '%txn:'.$transactionId.'%');
                }

                $existingTransaction = $existingTransactionQuery->first();

                if ($existingTransaction) {
                    return;
                }

                $currencyCode = strtoupper(GeneralSetting::getMetaValue('driver_recharge_currency') ?: 'INR');
                $amountPerDay = (float) (GeneralSetting::getMetaValue('driver_recharge_amount_per_day') ?: 30);
                $gstPercentage = round((float) (GeneralSetting::getMetaValue('driver_recharge_gst_percentage') ?: 0), 2);
                $durationDays = (int) ($request['duration_days'] ?? 0);
                $baseAmount = 0.0;

                if (! empty($request['plan_id'])) {
                    $plan = DriverRechargePlan::active()->find($request['plan_id']);
                    if (! $plan) {
                        throw new \RuntimeException('Recharge plan not found');
                    }
                    $durationDays = (int) $plan->duration_days;
                    $baseAmount = (float) $plan->amount;
                    $currencyCode = strtoupper($plan->currency_code ?: $currencyCode);
                } else {
                    if ($durationDays <= 0) {
                        throw new \RuntimeException('Duration is required when no plan is selected');
                    }
                    $baseAmount = round($durationDays * $amountPerDay, 2);
                }

                $gstAmount = round(($baseAmount * $gstPercentage) / 100, 2);
                $amount = round($baseAmount + $gstAmount, 2);

                if ($durationDays <= 0 || $baseAmount <= 0 || $amount <= 0) {
                    throw new \RuntimeException('Invalid recharge request');
                }

                $base = $driverLocked->recharge_valid_until && Carbon::parse($driverLocked->recharge_valid_until)->gt(Carbon::now())
                    ? Carbon::parse($driverLocked->recharge_valid_until)
                    : Carbon::now();

                $driverLocked->recharge_valid_until = $base->copy()->addDays($durationDays);
                $driverLocked->recharge_active = true;
                $driverLocked->save();

                $walletPayload = [
                    'vendor_id' => $driverLocked->id,
                    'amount' => $amount,
                    'type' => 'credit',
                    'description' => 'Driver recharge online payment via cashfree (txn:'.$transactionId.')',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (isset($walletColumns['payment_method'])) {
                    $walletPayload['payment_method'] = 'cashfree';
                }

                if (isset($walletColumns['payment_status'])) {
                    $walletPayload['payment_status'] = 'completed';
                }

                if (isset($walletColumns['txn_id'])) {
                    $walletPayload['txn_id'] = $transactionId;
                }

                if (isset($walletColumns['currency'])) {
                    $walletPayload['currency'] = $currencyCode;
                }

                if (isset($walletColumns['note'])) {
                    $walletPayload['note'] = 'Driver recharge online payment';
                }

                DB::table('vendor_wallets')->insert($walletPayload);

                $billing->createInvoice($driverLocked, [
                    'duration_days' => $durationDays,
                    'base_amount' => $baseAmount,
                    'gst_percentage' => $gstPercentage,
                    'gst_amount' => $gstAmount,
                    'amount' => $amount,
                    'currency_code' => $currencyCode,
                ], [
                    'plan_id' => $request['plan_id'] ?? null,
                    'payment_method' => 'cashfree',
                    'payment_status' => 'completed',
                    'transaction_id' => $transactionId,
                    'metadata' => [
                        'source' => 'cashfree_return',
                        'order_id' => $orderId,
                    ],
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Cashfree recharge confirmation failed', [
                'driver_id' => $driverId,
                'transaction_id' => $transactionId,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return route('wallet_recharge_fail', ['userToken' => $userToken]);
        }

        return route('wallet_recharge_success', ['userToken' => $userToken]);
    }

    private function getVendorWalletColumns(): array
    {
        if (! Schema::hasTable('vendor_wallets')) {
            return [];
        }

        return array_flip(Schema::getColumnListing('vendor_wallets'));
    }

    public function cancel($bookingId, $bookingData)
    {
        return '/payment_methods?booking='.$bookingId;
    }
}
