<?php

namespace App\Strategies;

use App\Http\Controllers\Traits\MiscellaneousTrait;
use App\Http\Controllers\Traits\PaymentStatusUpdaterTrait;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaypalStrategy implements PaymentStrategy
{
    use MiscellaneousTrait, PaymentStatusUpdaterTrait;

    private Client $client;

    private string $clientId;

    private string $clientSecret;

    public function __construct()
    {
        $paypalClientId = (string) $this->getGeneralSettingValue('live_paypal_client_id');
        $paypalClientSecret = (string) $this->getGeneralSettingValue('live_paypal_secret_key');

        $paypalTestClientId = (string) $this->getGeneralSettingValue('test_paypal_client_id');
        $paypalTestClientSecret = (string) $this->getGeneralSettingValue('test_paypal_secret_key');

        $paypalMode = $this->getGeneralSettingValue('paypal_options');
        $isTestMode = $paypalMode === 'test';

        $this->clientId = $isTestMode ? $paypalTestClientId : $paypalClientId;
        $this->clientSecret = $isTestMode ? $paypalTestClientSecret : $paypalClientSecret;

        $this->client = new Client([
            'base_uri' => $isTestMode ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com',
            'timeout' => 30,
        ]);
    }

    public function process($bookingId, $bookingData, $request)
    {
        try {
            $response = $this->paypalRequest('POST', '/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => 'USD',
                            'value' => (string) $bookingData->amount_to_pay,
                        ],
                        'custom_id' => (string) $bookingId,
                        'description' => 'Payment for booking: '.$bookingId,
                    ],
                ],
                'application_context' => [
                    'return_url' => route('handleReturn', ['booking' => $bookingId, 'method' => 'paypal']),
                    'cancel_url' => route('handleCancel', ['booking' => $bookingId, 'method' => 'paypal']),
                ],
            ]);

            $approvalUrl = null;
            foreach (($response['links'] ?? []) as $link) {
                if (($link['rel'] ?? null) === 'approve') {
                    $approvalUrl = $link['href'] ?? null;
                    break;
                }
            }

            if (! $approvalUrl) {
                return redirect('/invalid-order')->with('error', 'Invalid booking ID');
            }

            return redirect($approvalUrl)->with('success', 'Please make payment');

        } catch (\Throwable $e) {
            Log::error('PayPal create order failed', ['error' => $e->getMessage(), 'bookingId' => $bookingId]);

            return redirect('/invalid-order')->with('error', 'Invalid booking ID');
        }
    }

    public function cancel($bookingId, $bookingData)
    {
        return '/payment_methods?booking='.$bookingId;
    }

    public function return($bookingId, $requestData)
    {
        $token = $requestData instanceof Request
            ? $requestData->input('token')
            : ($requestData['token'] ?? null);

        if (! $token) {
            return '/payment_fail';
        }

        try {
            $result = $this->paypalRequest('POST', "/v2/checkout/orders/{$token}/capture");

            if (($result['status'] ?? null) === 'COMPLETED') {
                $transactionData = new \stdClass;
                $transactionData->response_data = json_encode($result);
                $transactionData->gateway_name = 'paypal';
                $transactionData->payment_status = 'completed';
                $transactionData->transaction_id = $result['purchase_units'][0]['payments']['captures'][0]['id']
                    ?? ($result['id'] ?? $token);

                $saveStatus = $this->updateBookingStatus($bookingId, $transactionData);
                $saveStatusData = json_decode($saveStatus, true);

                if ($saveStatusData['status'] === 'success') {
                    return '/payment_success';
                } else {
                    return '/payment_fail';
                }
            } else {
                return '/payment_fail';
            }
        } catch (\Throwable $e) {
            Log::error('PayPal capture failed', ['error' => $e->getMessage(), 'bookingId' => $bookingId, 'token' => $token]);

            return '/payment_fail';
        }
    }

    public function callback($bookingId, $requestData)
    {
        // PayPal doesn't require a separate callback handling
    }

    public function refund($bookingId, $bookingData) {}

    public function handleWebhook(Request $request)
    {
        $webhookData = $request->all();
        $eventType = $webhookData['event_type'] ?? null;

        switch ($eventType) {
            case 'PAYMENT.CAPTURE.COMPLETED':
                $this->handlePaymentCaptureCompleted($webhookData);
                break;
            case 'PAYMENT.CAPTURE.REFUNDED':
                $this->handlePaymentCaptureRefunded($webhookData);
                break;
                // Handle other event types as needed
            default:
                // Handle unknown event types
                break;
        }

        return response()->json(['status' => 'success']);
    }

    private function handlePaymentCaptureCompleted($webhookData)
    {
        $paymentId = $webhookData['resource']['id'];
        $bookingId = $webhookData['resource']['custom_id'];

        $transactionData = new \stdClass;
        $transactionData->response_data = json_encode($webhookData);
        $transactionData->gateway_name = 'paypal';
        $transactionData->payment_status = 'completed';
        $transactionData->transaction_id = $paymentId;

        $this->updateBookingStatus($bookingId, $transactionData);
    }

    private function handlePaymentCaptureRefunded($webhookData)
    {
        $paymentId = $webhookData['resource']['id'];
        $bookingId = $webhookData['resource']['custom_id'];

        $transactionData = new \stdClass;
        $transactionData->response_data = json_encode($webhookData);
        $transactionData->gateway_name = 'paypal';
        $transactionData->payment_status = 'refunded';
        $transactionData->transaction_id = $paymentId;

        $this->updateBookingStatus($bookingId, $transactionData);
    }

    private function paypalRequest(string $method, string $uri, array $payload = []): array
    {
        $accessToken = $this->getAccessToken();
        if (! $accessToken) {
            throw new \RuntimeException('Unable to generate PayPal access token');
        }

        $options = [
            'headers' => [
                'Authorization' => 'Bearer '.$accessToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ];

        if (! empty($payload)) {
            $options['json'] = $payload;
        }

        $response = $this->client->request($method, $uri, $options);

        return json_decode((string) $response->getBody(), true) ?? [];
    }

    private function getAccessToken(): ?string
    {
        $response = $this->client->post('/v1/oauth2/token', [
            'auth' => [$this->clientId, $this->clientSecret],
            'form_params' => [
                'grant_type' => 'client_credentials',
            ],
            'headers' => [
                'Accept' => 'application/json',
                'Accept-Language' => 'en_US',
            ],
        ]);

        $payload = json_decode((string) $response->getBody(), true);

        return $payload['access_token'] ?? null;
    }
}
