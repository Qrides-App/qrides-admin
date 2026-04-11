<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ResponseTrait;
use App\Models\AppUser;
use Illuminate\Http\Request;
use Validator;

class TokenController extends Controller
{
    use ResponseTrait;

    public function issueSanctumToken(Request $request)
    {
        $sanctumSecret = (string) env('SANCTUM_ISSUE_SECRET', '');
        $allowGuestTokens = filter_var(env('ALLOW_GUEST_TOKENS', false), FILTER_VALIDATE_BOOL);

        if ($sanctumSecret === '') {
            return $this->addSuccessResponse(503, 'Token service is not configured.', []);
        }

        $validator = Validator::make($request->all(), [
            'secret' => 'required|string',
            'user_token' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorComputing($validator);
        }

        if (! hash_equals($sanctumSecret, (string) $request->secret)) {
            return $this->addSuccessResponse(498, trans('front.Unauthorized'), []);

        }
        $isRealUser = false;
        if ($request->filled('user_token')) {

            $user = AppUser::where('token', $request->input('user_token'))->first();
            if (! $user) {
                return $this->addSuccessResponse(419, 'Invalid user token.', []);
            }
            $user->tokens()->delete();
            $isRealUser = true;
        } else {
            if (! $allowGuestTokens) {
                return $this->addSuccessResponse(403, 'Guest token flow is disabled.', []);
            }
            $user = AppUser::firstOrCreate(
                ['email' => 'guest@unibooker.app'],// Never delete this user
                ['first_name' => 'Guest User', 'user_type' => 'guest', 'password' => bcrypt('f07c02db6c1c42289f58')]
            );
        }

        $tokenInstance = $user->createToken('api-access');
        $token = $tokenInstance->plainTextToken;
        $expiration = $isRealUser ? now()->addDays(7) : now()->addMinutes(30);
        $tokenInstance->accessToken->expires_at = $expiration;
        $tokenInstance->accessToken->called_ip = $request->ip();
        $tokenInstance->accessToken->save();

        return $this->addSuccessResponse(200, trans('front.authorized'), [
            'token' => $token,
            'type' => $request->type,
            'expires_at' => $expiration,
        ]);

    }
}
