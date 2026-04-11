<?php

namespace App\Strategies;

use App\Http\Controllers\Traits\MiscellaneousTrait;
use App\Http\Controllers\Traits\PaymentStatusUpdaterTrait;
use Illuminate\Support\Facades\Http;

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
                'return_url' => url('/payment/return?booking='.$bookingId.'&method=cashfree&order_id='.$orderId).'&cf_id={order_id}&cf_token={order_token}',
            ],
        ];

        $response = $this->createOrder($payload);
        if ($response['status'] !== 'success') {
            return redirect('/invalid-order')->with('error', $response['message'] ?? 'Cashfree order failed.');
        }

        $data = $response['data'];
        return redirect($data['payment_link']);
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
        $orderId = 'recharge_'.$userID.'_'.time();
        $payload = [
            'order_id' => $orderId,
            'order_amount' => (float) $amount,
            'order_currency' => $currency,
            'customer_details' => [
                'customer_id' => 'driver_'.$userID,
            ],
            'order_meta' => [
                'return_url' => url('/payment/return?booking='.$orderId.'&method=cashfree').'&cf_id={order_id}&cf_token={order_token}',
            ],
        ];
        $response = $this->createOrder($payload);
        if ($response['status'] !== 'success') {
            return redirect('/invalid-order')->with('error', $response['message'] ?? 'Cashfree order failed.');
        }
        return redirect($response['data']['payment_link']);
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
