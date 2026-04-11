<?php

namespace App\Services;

use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class ExotelService
{
    private function getSettingValue(string $metaKey, string $envFallback = '', bool $encrypted = false): string
    {
        $value = GeneralSetting::where('meta_key', $metaKey)->value('meta_value');
        if (! empty($value)) {
            if ($encrypted) {
                try {
                    return (string) Crypt::decryptString((string) $value);
                } catch (\Throwable $e) {
                    // Backward compatibility for previously plain values.
                }
            }

            return (string) $value;
        }

        return trim((string) $envFallback);
    }

    private function sid(): string
    {
        return $this->getSettingValue('exotel_sid', config('exotel.sid'));
    }

    private function token(): string
    {
        return $this->getSettingValue('exotel_token', config('exotel.token'), true);
    }

    private function fromNumber(): string
    {
        return $this->getSettingValue('exotel_virtual_number', config('exotel.from'));
    }

    private function baseUrl(): string
    {
        return rtrim($this->getSettingValue('exotel_base_url', config('exotel.base_url', 'https://api.exotel.com/v1/Accounts')), '/');
    }

    private function callbackToken(): string
    {
        return $this->getSettingValue('exotel_callback_token', config('exotel.callback_token'), true);
    }

    public function isConfigured(): bool
    {
        return count($this->missingConfigFields()) === 0;
    }

    public function missingConfigFields(): array
    {
        $required = [
            'EXOTEL_SID' => $this->sid(),
            'EXOTEL_TOKEN' => $this->token(),
            'EXOTEL_VIRTUAL_NUMBER' => $this->fromNumber(),
        ];

        return array_keys(array_filter($required, static fn ($value) => empty($value)));
    }

    public function connectMaskedCall(string $caller, string $callee, ?string $recordingUrl = null)
    {
        $sid = $this->sid();
        $token = $this->token();
        $from = $this->fromNumber();
        $base = $this->baseUrl();
        $callbackToken = $this->callbackToken();

        if (! $this->isConfigured()) {
            return [
                'ok' => false,
                'message' => 'Call masking is not configured yet. Please set Exotel credentials in server .env.',
                'code' => 503,
                'missing' => $this->missingConfigFields(),
            ];
        }

        $callbackId = $callbackToken ?: Str::uuid()->toString();

        $payload = [
            'From' => $caller,
            'To' => $callee,
            'CallerId' => $from,
            // API route lives under /api/v1 in this project.
            'StatusCallback' => url('/api/v1/exotel/callback'),
            'StatusCallbackToken' => $callbackId,
        ];

        if ($recordingUrl) {
            $payload['RecordingUrl'] = $recordingUrl;
        }

        $url = "$base/$sid/Calls/connect";

        try {
            $response = Http::withBasicAuth($sid, $token)
                ->asForm()
                ->post($url, $payload);

            if (! $response->successful()) {
                Log::warning('Exotel connect failed', ['status' => $response->status(), 'body' => $response->body()]);
                return [
                    'ok' => false,
                    'message' => 'Exotel API error',
                    'code' => $response->status(),
                ];
            }

            $data = $response->json();
            return ['ok' => true, 'data' => $data];
        } catch (\Throwable $e) {
            Log::error('Exotel connect exception', ['error' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'Exception contacting Exotel'];
        }
    }
}
