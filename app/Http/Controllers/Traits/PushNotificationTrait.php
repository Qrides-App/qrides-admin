<?php

namespace App\Http\Controllers\Traits;

use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait PushNotificationTrait
{
    private function getFirebaseServerKey(): ?string
    {
        $serverKey = GeneralSetting::getMetaValue('firebase_server_key');
        if (! empty($serverKey)) {
            return trim((string) $serverKey);
        }

        $envKey = env('FCM_SERVER_KEY');

        return empty($envKey) ? null : trim((string) $envKey);
    }

    /**
     * Send a push notification to a single device via FCM.
     *
     * @param  string  $deviceToken
     * @param  string  $title
     * @param  string  $body
     * @param  array  $data  Optional data payload
     * @return void
     */
    public function sendFcmMessage($deviceToken, $subject, $message, $data = [], $vendorNotification = 0, $userType = null): bool
    {
        if (empty($deviceToken)) {
            Log::warning('FCM single send skipped: missing device token.');

            return false;
        }

        $serverKey = $this->getFirebaseServerKey();
        if (empty($serverKey)) {
            Log::warning('FCM single send skipped: missing firebase server key.');

            return false;
        }

        $payloadData = $this->parseBookingData($data);
        $payloadData['vendorNotification'] = $vendorNotification;

        $url = 'https://fcm.googleapis.com/fcm/send';
        $payload = [
            'to' => $deviceToken,
            'notification' => [
                'title' => (string) $subject,
                'body' => (string) $message,
            ],
            'data' => $payloadData,
            'priority' => 'high',
        ];

        $headers = [
            'Authorization' => 'key='.$serverKey,
            'Content-Type' => 'application/json',
        ];

        $response = Http::withHeaders($headers)->post($url, $payload);
        if ($response->failed()) {
            Log::error('FCM single notification failed', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;
        }

        Log::info('FCM single notification sent', [
            'subject' => $subject,
            'user_type' => $userType,
        ]);

        return true;
    }

    /**
     * Send a push notification to multiple devices via FCM.
     *
     * @param  string  $title
     * @param  string  $body
     * @param  array  $data  Optional data payload
     * @return void
     */
    public function sendPushNotificationsToDevices(array $deviceTokens, $title, $body, $data = [])
    {
        if (empty($deviceTokens)) {
            return;
        }

        $url = 'https://fcm.googleapis.com/fcm/send';
        $serverKey = $this->getFirebaseServerKey();
        if (empty($serverKey)) {
            Log::warning('FCM batch send skipped: missing firebase server key.');

            return;
        }

        $payload = [
            'registration_ids' => $deviceTokens,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $data,
        ];

        $headers = [
            'Authorization' => 'key='.$serverKey,
            'Content-Type' => 'application/json',
        ];

        $response = Http::withHeaders($headers)->post($url, $payload);

        if ($response->failed()) {
            Log::error('Failed to send push notifications', ['response' => $response->body()]);
        }
    }

    /**
     * Send a push notification to all devices subscribed to a particular topic via FCM.
     *
     * @param  string  $topic
     * @param  string  $title
     * @param  string  $body
     * @param  array  $data  Optional data payload
     * @return void
     */
    public function sendPushNotificationToTopic($topic, $title, $body, $data = [])
    {
        $url = 'https://fcm.googleapis.com/fcm/send';
        $serverKey = $this->getFirebaseServerKey();
        if (empty($serverKey)) {
            Log::warning('FCM topic send skipped: missing firebase server key.');

            return;
        }

        $payload = [
            'to' => '/topics/'.$topic,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $data,
        ];

        $headers = [
            'Authorization' => 'key='.$serverKey,
            'Content-Type' => 'application/json',
        ];

        $response = Http::withHeaders($headers)->post($url, $payload);

        if ($response->failed()) {
            Log::error('Failed to send push notification to topic', ['response' => $response->body()]);
        }
    }

    private function parseBookingData($data)
    {

        $checkIn = $this->extractValue($data, 'check_in');

        if ($checkIn !== null) {
            $bookingStatus = $this->extractValue($data, 'status') ?? 'Unknown';

            return [
                'status' => $bookingStatus,
                'route' => 'booking',
            ];
        }
        $guestRating = $this->extractValue($data, 'guest_rating');
        $hostRating = $this->extractValue($data, 'host_rating');

        // If either guest_rating or host_rating exists, set route to 'review'
        if ($guestRating !== null || $hostRating !== null) {
            return [
                'route' => 'review',
            ];
        }

        return [

            'route' => 'none',
        ];
    }

    private function extractValue($data, $key)
    {

        if (is_array($data) || is_object($data)) {

            if (isset($data[$key])) {
                return $data[$key];
            }

            foreach ($data as $item) {
                $result = $this->extractValue($item, $key);
                if ($result !== null) {
                    return $result;
                }
            }
        }

        return null;
    }
}
