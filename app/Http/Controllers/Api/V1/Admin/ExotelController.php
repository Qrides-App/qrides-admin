<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneralSetting;
use App\Services\ExotelService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ExotelController extends Controller
{
    private ExotelService $exotel;

    private function callbackToken(): string
    {
        $value = GeneralSetting::where('meta_key', 'exotel_callback_token')->value('meta_value');
        if (! empty($value)) {
            try {
                return (string) Crypt::decryptString((string) $value);
            } catch (\Throwable $e) {
                return (string) $value;
            }
        }

        return (string) config('exotel.callback_token', '');
    }

    public function __construct(ExotelService $exotel)
    {
        $this->exotel = $exotel;
    }

    public function maskedCall(Request $request)
    {
        if (! $this->exotel->isConfigured()) {
            return response()->json([
                'status' => 503,
                'message' => 'Call masking is not configured yet. Please contact support.',
                'data' => [
                    'missing' => $this->exotel->missingConfigFields(),
                ],
            ], 503);
        }

        $validator = Validator::make($request->all(), [
            'caller' => 'required|string', // customer number
            'callee' => 'required|string', // driver number
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'message' => $validator->errors()->first()], 422);
        }

        $result = $this->exotel->connectMaskedCall($request->caller, $request->callee);
        if (! $result['ok']) {
            return response()->json([
                'status' => $result['code'] ?? 500,
                'message' => $result['message'] ?? 'Unable to connect call.',
                'data' => [
                    'missing' => $result['missing'] ?? [],
                ],
            ], $result['code'] ?? 500);
        }

        return response()->json(['status' => 200, 'data' => $result['data']]);
    }

    // Exotel webhook for call status
    public function callback(Request $request)
    {
        $expected = $this->callbackToken();
        $received = (string) $request->input('StatusCallbackToken', $request->header('X-Exotel-Token', ''));
        if ($expected !== '' && ! hash_equals($expected, $received)) {
            Log::warning('Exotel callback rejected due to invalid token', [
                'token_present' => $received !== '',
                'keys' => array_keys($request->all()),
            ]);

            return response('Forbidden', 403);
        }

        Log::info('Exotel callback', Arr::except($request->all(), ['CallSid', 'RecordingUrl']));
        return response('OK');
    }
}
