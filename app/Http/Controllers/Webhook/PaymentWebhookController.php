<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\PaymentStatusUpdaterTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaymentWebhookController extends Controller
{
    use PaymentStatusUpdaterTrait;

    /**
     * Razorpay webhook handler (payments.captured).
     * Verifies signature using RAZORPAY_WEBHOOK_SECRET and fetches order to map to booking via receipt.
     */
    public function razorpay(Request $request)
    {
        $secret = env('RAZORPAY_WEBHOOK_SECRET');
        if (! $secret) {
            return response('Webhook secret not set', 400);
        }

        $body = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature');
        $expected = hash_hmac('sha256', $body, $secret);
        if (! hash_equals($expected, (string) $signature)) {
            return response('Invalid signature', 400);
        }

        $payment = $request->input('payload.payment.entity', []);
        $status = $payment['status'] ?? '';
        $orderId = $payment['order_id'] ?? null;
        if (! $orderId) {
            return response('No order id', 200);
        }

        // Fetch order to get receipt we set (order_{bookingId})
        $order = $this->fetchRazorpayOrder($orderId);
        $receipt = $order['receipt'] ?? '';
        if (! $receipt || ! str_starts_with($receipt, 'order_')) {
            return response('No booking receipt', 200);
        }
        $bookingId = (int) str_replace('order_', '', $receipt);
        if ($bookingId <= 0) {
            return response('Invalid booking id', 200);
        }

        if ($status === 'captured') {
            $txn = new \stdClass;
            $txn->response_data = json_encode($payment);
            $txn->gateway_name = 'razorpay';
            $txn->payment_status = 'completed';
            $txn->transaction_id = $payment['id'] ?? '';
            $this->updateBookingStatus($bookingId, $txn);
        }

        return response('ok', 200);
    }

    /**
     * Cashfree webhook handler.
     * Signature: x-webhook-signature = base64(hmac SHA256 (rawBody, secretKey))
     */
    public function cashfree(Request $request)
    {
        $body = $request->getContent();
        $signature = $request->header('x-webhook-signature');
        $secret = env('CASHFREE_WEBHOOK_SECRET', env('CASHFREE_SECRET_KEY'));
        if (! $secret) {
            return response('Webhook secret not set', 400);
        }
        $computed = base64_encode(hash_hmac('sha256', $body, $secret, true));
        if (! hash_equals($computed, (string) $signature)) {
            return response('Invalid signature', 400);
        }

        $payload = $request->json()->all();
        $orderStatus = $payload['order']['status'] ?? $payload['order_status'] ?? '';
        $orderId = $payload['order']['id'] ?? $payload['order_id'] ?? '';
        if (! $orderId || ! str_starts_with($orderId, 'booking_')) {
            return response('ignored', 200);
        }
        // order_id format: booking_{id}_timestamp
        $parts = explode('_', $orderId);
        $bookingId = isset($parts[1]) ? (int) $parts[1] : 0;
        if ($bookingId <= 0) {
            return response('ignored', 200);
        }

        if (strtoupper($orderStatus) === 'PAID') {
            $txn = new \stdClass;
            $txn->response_data = json_encode($payload);
            $txn->gateway_name = 'cashfree';
            $txn->payment_status = 'completed';
            $txn->transaction_id = $payload['payment']['payment_id'] ?? ($payload['cf_payment_id'] ?? '');
            $this->updateBookingStatus($bookingId, $txn);
        }

        return response('ok', 200);
    }

    private function fetchRazorpayOrder(string $orderId): array
    {
        $key = env('RAZORPAY_KEY_ID', env('RAZORPAY_KEY', ''));
        $secret = env('RAZORPAY_KEY_SECRET', '');
        if (! $key || ! $secret) {
            return [];
        }

        $resp = Http::withBasicAuth($key, $secret)
            ->get("https://api.razorpay.com/v1/orders/{$orderId}");

        return $resp->ok() ? $resp->json() : [];
    }
}
