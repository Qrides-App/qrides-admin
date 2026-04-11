<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ExotelService
{
    public function isConfigured(): bool
    {
        return count($this->missingConfigFields()) === 0;
    }

    public function missingConfigFields(): array
    {
        $required = [
            'EXOTEL_SID' => config('exotel.sid'),
            'EXOTEL_TOKEN' => config('exotel.token'),
            'EXOTEL_VIRTUAL_NUMBER' => config('exotel.from'),
        ];

        return array_keys(array_filter($required, static fn ($value) => empty($value)));
    }

    public function connectMaskedCall(string $caller, string $callee, ?string $recordingUrl = null)
    {
        $sid = config('exotel.sid');
        $token = config('exotel.token');
        $from = config('exotel.from');
        $base = rtrim(config('exotel.base_url'), '/');
        $callbackToken = config('exotel.callback_token');

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
