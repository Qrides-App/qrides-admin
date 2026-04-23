<?php

namespace App\Http\Controllers\Traits;

use App\Models\GeneralSetting;
use Google\Client;
use Illuminate\Support\Facades\File;
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

    private function getFirebaseCredentialsPath(): string
    {
        $envPath = trim((string) env('FIREBASE_CREDENTIALS_PATH', ''));
        if ($envPath !== '' && File::exists($envPath)) {
            return $envPath;
        }

        $renderSecretPath = '/etc/secrets/firebase_credentials.json';
        if (File::exists($renderSecretPath)) {
            return $renderSecretPath;
        }

        return storage_path('firebase/firebase_credentials.json');
    }

    private function getFirebaseProjectId(): ?string
    {
        $envProject = trim((string) env('FIREBASE_PROJECT_ID', ''));
        if ($envProject !== '') {
            return $envProject;
        }

        $credentialsPath = $this->getFirebaseCredentialsPath();
        if (! is_readable($credentialsPath)) {
            return null;
        }

        try {
            $json = json_decode((string) file_get_contents($credentialsPath), true, 512, JSON_THROW_ON_ERROR);
            $projectId = trim((string) ($json['project_id'] ?? ''));

            return $projectId !== '' ? $projectId : null;
        } catch (\Throwable $e) {
            Log::warning('Unable to parse Firebase credentials for project id.', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function shouldUseFcmHttpV1(): bool
    {
        $mode = strtolower((string) env('FCM_PREFERRED_API', 'auto')); // auto|v1|legacy

        if ($mode === 'legacy') {
            return false;
        }

        $hasCredentials = is_readable($this->getFirebaseCredentialsPath());
        $hasProjectId = ! empty($this->getFirebaseProjectId());

        if ($mode === 'v1') {
            return $hasCredentials && $hasProjectId;
        }

        return $hasCredentials && $hasProjectId;
    }

    private function normalizeDataPayload(array $data): array
    {
        $normalized = [];

        foreach ($data as $key => $value) {
            if (is_null($value)) {
                $normalized[(string) $key] = '';
            } elseif (is_scalar($value)) {
                $normalized[(string) $key] = (string) $value;
            } else {
                $normalized[(string) $key] = json_encode($value);
            }
        }

        return $normalized;
    }

    private function sendWithFcmHttpV1(array $message): bool
    {
        $credentialsPath = $this->getFirebaseCredentialsPath();
        $projectId = $this->getFirebaseProjectId();
        if (! is_readable($credentialsPath) || empty($projectId)) {
            return false;
        }

        try {
            $client = new Client;
            $client->setAuthConfig($credentialsPath);
            $client->setScopes(['https://www.googleapis.com/auth/firebase.messaging']);
            $tokenData = $client->fetchAccessTokenWithAssertion();
            $accessToken = $tokenData['access_token'] ?? null;

            if (empty($accessToken)) {
                Log::error('FCM HTTP v1 token generation failed.', ['token_response' => $tokenData]);

                return false;
            }

            $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
            $response = Http::withToken($accessToken)->post($url, [
                'message' => $message,
            ]);

            if ($response->failed()) {
                Log::error('FCM HTTP v1 send failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('FCM HTTP v1 send exception', ['error' => $e->getMessage()]);

            return false;
        }
    }

    private function sendWithFcmLegacy(array $payload): bool
    {
        $serverKey = $this->getFirebaseServerKey();
        if (empty($serverKey)) {
            Log::warning('FCM legacy send skipped: missing firebase server key.');

            return false;
        }

        $response = Http::withHeaders([
            'Authorization' => 'key='.$serverKey,
            'Content-Type' => 'application/json',
        ])->post('https://fcm.googleapis.com/fcm/send', $payload);

        if ($response->failed()) {
            Log::error('FCM legacy send failed', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;
        }

        return true;
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

        $payloadData = $this->parseBookingData($data);
        $payloadData['vendorNotification'] = $vendorNotification;
        $payloadData = $this->normalizeDataPayload($payloadData);

        if ($this->shouldUseFcmHttpV1()) {
            $sent = $this->sendWithFcmHttpV1([
                'token' => (string) $deviceToken,
                'notification' => [
                    'title' => (string) $subject,
                    'body' => (string) $message,
                ],
                'data' => $payloadData,
                'android' => [
                    'priority' => 'high',
                ],
            ]);

            if ($sent) {
                Log::info('FCM HTTP v1 single notification sent', [
                    'subject' => $subject,
                    'user_type' => $userType,
                ]);

                return true;
            }

            Log::warning('FCM HTTP v1 failed; attempting legacy fallback for single notification.');
        }

        $legacyPayload = [
            'to' => (string) $deviceToken,
            'notification' => [
                'title' => (string) $subject,
                'body' => (string) $message,
            ],
            'data' => $payloadData,
            'priority' => 'high',
        ];

        $sent = $this->sendWithFcmLegacy($legacyPayload);
        if (! $sent) {
            return false;
        }

        Log::info('FCM legacy single notification sent', [
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

        $normalizedTokens = array_values(array_filter(array_unique(array_map('strval', $deviceTokens))));
        if (empty($normalizedTokens)) {
            return;
        }

        $normalizedData = $this->normalizeDataPayload((array) $data);

        if ($this->shouldUseFcmHttpV1()) {
            $allOk = true;
            foreach ($normalizedTokens as $token) {
                $ok = $this->sendWithFcmHttpV1([
                    'token' => $token,
                    'notification' => [
                        'title' => (string) $title,
                        'body' => (string) $body,
                    ],
                    'data' => $normalizedData,
                    'android' => [
                        'priority' => 'high',
                    ],
                ]);
                $allOk = $allOk && $ok;
            }

            if ($allOk) {
                return;
            }

            Log::warning('FCM HTTP v1 batch send partially failed; attempting legacy fallback.');
        }

        $legacyPayload = [
            'registration_ids' => $normalizedTokens,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $normalizedData,
        ];

        $this->sendWithFcmLegacy($legacyPayload);
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
        $normalizedData = $this->normalizeDataPayload((array) $data);

        if ($this->shouldUseFcmHttpV1()) {
            $sent = $this->sendWithFcmHttpV1([
                'topic' => (string) $topic,
                'notification' => [
                    'title' => (string) $title,
                    'body' => (string) $body,
                ],
                'data' => $normalizedData,
                'android' => [
                    'priority' => 'high',
                ],
            ]);

            if ($sent) {
                return;
            }

            Log::warning('FCM HTTP v1 topic send failed; attempting legacy fallback.');
        }

        $legacyPayload = [
            'to' => '/topics/'.$topic,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $normalizedData,
        ];

        $this->sendWithFcmLegacy($legacyPayload);
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
