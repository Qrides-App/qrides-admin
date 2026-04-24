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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class RazorpayStrategy implements PaymentStrategy
{
    use MiscellaneousTrait, PaymentStatusUpdaterTrait;

    private $apiURL;

    private $apiKey;

    private $apiSecret;

    public function __construct()
    {
        $this->apiURL = 'https://api.razorpay.com/v1/orders';
        $environment = $this->getGeneralSettingValue('razorpay_options');
        $this->apiKey = $this->getGeneralSettingValue($environment === 'live' ? 'live_razorpay_key_id' : 'test_razorpay_key_id');
        $this->apiSecret = $this->getGeneralSettingValue($environment === 'live' ? 'live_razorpay_secret_key' : 'test_razorpay_secret_key');

    }

    /**
     * Create an order and return payload.
     */
    public function createOrder($amount, $currency, $receipt, array $notes = [])
    {
        $postFields = [
            'amount' => (int) round($amount * 100),
            'currency' => $currency,
            'payment_capture' => 1,
            'receipt' => $receipt,
            'notes' => $notes,
        ];
        if (! empty($notes)) {
            $postFields['notes'] = $notes;
        }

        $response = $this->makeCurlRequest($this->apiURL, $postFields);
        if ($response['status'] === 'success') {
            return [
                'status' => 'success',
                'data' => json_decode($response['data'], true),
                'key_id' => $this->apiKey,
            ];
        }

        return [
            'status' => 'error',
            'message' => $response['message'] ?? 'Unable to create order',
        ];
    }

    /**
     * Verify signature returned by Razorpay checkout.
     */
    public function verifySignature($orderId, $paymentId, $signature)
    {
        $generatedSignature = hash_hmac('sha256', $orderId.'|'.$paymentId, $this->apiSecret);
        return hash_equals($generatedSignature, $signature);
    }

    public function rechargeWallet($userID, $amount, $currency, $request)
    {
        $planId = $request->input('plan_id');
        $durationDays = $request->input('duration_days');
        $userToken = $request->input('userToken');
        $idempotencyKey = $request->input('idempotency_key');

        $orderPayload = $this->createOrder(
            $amount,
            $currency,
            'recharge_'.$userID.'_'.time(),
            array_filter([
                'driver_id' => (string) $userID,
                'plan_id' => $planId ? (string) $planId : null,
                'duration_days' => $durationDays ? (string) $durationDays : null,
                'idempotency_key' => $idempotencyKey ?: null,
            ], static fn ($value) => $value !== null && $value !== '')
        );

        if ($orderPayload['status'] !== 'success') {
            return redirect()->route('wallet_recharge_fail', ['userToken' => $userToken])
                ->with('error', $orderPayload['message'] ?? 'Payment initiation failed.');
        }

        return view('Front.WalletRecharge.razorpay-payment', [
            'orderDetails' => $orderPayload['data'],
            'apiKey' => $orderPayload['key_id'] ?? $this->apiKey,
            'userToken' => $userToken,
            'planId' => $planId,
            'durationDays' => $durationDays,
        ]);
    }

    public function process($bookingId, $bookingData, $request)
    {

        $postFields = [
            'amount' => $bookingData->amount_to_pay * 100,
            'currency' => $bookingData->currency_code,
            'payment_capture' => 1,
            'receipt' => 'order_'.$bookingId,
        ];

        $response = $this->makeCurlRequest($this->apiURL, $postFields);

        if ($response['status'] === 'error') {

            return redirect('/invalid-order')->with('error', $response['message'] ?? 'Payment initiation failed.');
        }

        $result = json_decode($response['data'], true);
        Log::info('Razorpay Response:', $result);
        if (isset($result['status']) && $result['status'] === 'created') {
            Log::info('ifRazorpay Response:', $result);

            return view('Front.razorpay-payment', [
                'bookingId' => $bookingId,
                'orderDetails' => $result,
                'apiKey' => $this->apiKey,
            ]);

        } else {
            Log::info('elseRazorpay Response:', $result);

            return redirect('/invalid-order')->with('error', $result['Message'] ?? 'Payment initiation failed.');
        }
    }

    public function return($bookingId, $request)
    {
        $razorpayResponse = $request->all();
        $isWalletRecharge = (($request['wallet_recharge'] ?? null) == '1' || ! empty($request['token']));

        $orderId = $request['razorpay_order_id'] ?? null;
        $paymentId = $request['razorpay_payment_id'] ?? null;
        $signature = $request['razorpay_signature'] ?? null;

        if (! $orderId || ! $paymentId || ! $signature) {
            if ($isWalletRecharge) {
                return route('wallet_recharge_fail', ['userToken' => $request['token'] ?? null]);
            }
            return view('Front.Fail');
        }

        if (! $this->verifySignature($orderId, $paymentId, $signature)) {
            if ($isWalletRecharge) {
                return route('wallet_recharge_fail', ['userToken' => $request['token'] ?? null]);
            }
            return view('Front.Fail');
        }

        if ($isWalletRecharge) {
            return $this->handleRechargePaymentStatus($request, $paymentId, $razorpayResponse);
        }

        return $this->handlePaymentStatus($bookingId, $paymentId, $razorpayResponse);
    }

    public function callback($bookingId, $request)
    {
        $paymentId = $request->query('paymentId');
        if (! $paymentId) {
            return '/payment_fail';
        }

        return $this->handlePaymentStatus($bookingId, $paymentId, $request->all());
    }

    private function handlePaymentStatus($bookingId, $paymentId, $razorpayResponse = [])
    {

        if (isset($razorpayResponse['razorpay_payment_id'])) {

            $transactionData = new \stdClass;
            $transactionData->response_data = json_encode($razorpayResponse);
            $transactionData->gateway_name = 'razorpay';
            $transactionData->payment_status = 'completed';
            $transactionData->transaction_id = $paymentId;

            $saveStatus = $this->updateBookingStatus($bookingId, $transactionData);

            $saveStatusData = json_decode($saveStatus, true);

            if ($saveStatusData['status'] === 'success') {

                return '/payment_success';

            } else {
                return '/payment_fail';
                // return redirect('/payment_fail')->with('error', 'Failed to update booking status.');
            }
        } else {
            // Payment failed
            return view('Front.Fail');
        }
    }

    private function handleRechargePaymentStatus(array $request, $paymentId, array $razorpayResponse = [])
    {
        $userToken = $request['token'] ?? null;
        if (! $userToken) {
            return route('wallet_recharge_fail');
        }

        $driverId = $this->checkUserByToken($userToken);
        if (! $driverId) {
            return route('wallet_recharge_fail', ['userToken' => $userToken]);
        }

        try {
            DB::transaction(function () use ($driverId, $request, $paymentId, $razorpayResponse) {
                $walletColumns = $this->getVendorWalletColumns();
                $billing = new RechargeBillingService();
                $driverLocked = AppUser::where('id', $driverId)
                    ->where('user_type', 'driver')
                    ->lockForUpdate()
                    ->firstOrFail();

                $existingTransactionQuery = DB::table('vendor_wallets')
                    ->where('vendor_id', $driverLocked->id);

                if (isset($walletColumns['payment_method'])) {
                    $existingTransactionQuery->where('payment_method', 'razorpay');
                }

                if (isset($walletColumns['txn_id'])) {
                    $existingTransactionQuery->where('txn_id', $paymentId);
                } else {
                    $existingTransactionQuery->where('description', 'like', '%txn:'.$paymentId.'%');
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
                    'description' => 'Driver recharge online payment via razorpay (txn:'.$paymentId.')',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (isset($walletColumns['payment_method'])) {
                    $walletPayload['payment_method'] = 'razorpay';
                }

                if (isset($walletColumns['payment_status'])) {
                    $walletPayload['payment_status'] = 'completed';
                }

                if (isset($walletColumns['txn_id'])) {
                    $walletPayload['txn_id'] = $paymentId;
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
                    'payment_method' => 'razorpay',
                    'payment_status' => 'completed',
                    'transaction_id' => $paymentId,
                    'metadata' => [
                        'source' => 'razorpay_return',
                        'gateway_response' => $razorpayResponse,
                    ],
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Razorpay recharge confirmation failed', [
                'driver_id' => $driverId,
                'payment_id' => $paymentId,
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

    public function refund($bookingId, $bookingData) {}

    public function makeCurlRequest($url, $postFields)
    {
        $ch = curl_init();
        $postData = json_encode($postFields);
        if ($postData === false) {
            return [
                'status' => 'error',
                'message' => 'JSON encoding error: '.json_last_error_msg(),
            ];
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

        $apiKey = trim($this->apiKey);
        $apiSecret = trim($this->apiSecret);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic '.base64_encode("{$apiKey}:{$apiSecret}"),
        ]);

        $response = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);

            return [
                'status' => 'error',
                'message' => $error,
            ];
        }

        curl_close($ch);

        if ($httpStatus >= 400) {

            return [
                'status' => 'error',
                'message' => "HTTP Error: $httpStatus, Response: $response",
            ];
        }

        return [
            'status' => 'success',
            'data' => $response,
        ];
    }

    public function paymentError()
    {
        return view('Front.Fail');
    }

    public function cancel($bookingId, $bookingData)
    {
        return '/payment_methods?booking='.$bookingId;
    }
}
