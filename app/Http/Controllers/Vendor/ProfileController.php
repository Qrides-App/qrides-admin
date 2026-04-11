<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Controllers\Traits\NotificationTrait;
use App\Models\AppUser;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;
use Validator;

class ProfileController extends Controller
{
    use MediaUploadingTrait, NotificationTrait;

    public function index()
    {

        if (auth()->check()) {
            $user = auth()->user();
            $vendorId = $user->id;
        }

        $appUser = AppUser::where('id', $vendorId)->first();

        return view('vendor.profile.index', compact('appUser'));
    }

    public function updateProfile(Request $request, AppUser $appUser)
    {

        $appUserDetail = AppUser::where('email', $request->email)->first();

        // $appUser->update($request->all());
        $data = $request->except('password');
        if (! empty($request->input('password'))) {
            $data['password'] = Hash::make($request->input('password'));
        }
        $appUser->update($data);

        if ($request->input('profile_image', false)) {
            if (! $appUser->profile_image || $request->input('profile_image') !== $appUser->profile_image->file_name) {
                if ($appUser->profile_image) {
                    $appUser->profile_image->delete();
                }
                $appUser->addMedia(storage_path('tmp/uploads/'.basename($request->input('profile_image'))))->toMediaCollection('profile_image');
            }
        } elseif ($appUser->profile_image) {
            $appUser->profile_image->delete();
        }

        return redirect()->route('vendor.profile');
    }

    public function checkEmail(Request $request)
    {

        $user = AppUser::where('token', $request->input('token'))->first();

        if (! $user) {
            return $this->addErrorResponse(404, trans('global.user_not_found'), '');
        }

        $existingUser = AppUser::where('email', $request->input('email'))
            ->where('id', '!=', $user->id)
            ->withTrashed()
            ->first();

        if ($existingUser) {
            return response()->json([
                'status' => 400,
                'message' => trans('global.email_already_exists'),
            ], 400);
        }

        if ($user->email == $request->input('email')) {
            return response()->json([
                'status' => 400,
                'message' => trans('global.email_same_as_current'),
            ], 400);
        }

        $data = [
            'token' => $request->input('token'),
            'email' => $request->input('email'),
        ];

        try {
            $response = Http::post(url('api/v1/checkEmail'), $data);

            if ($response->successful()) {
                $responseData = $response->json();

                // Return both status and data part of the response
                return response()->json([
                    'status' => $responseData['status'],
                    'data' => $responseData['data'],
                ]);
            } else {
                return response()->json([
                    'status' => $response->status(),
                    'error' => $response->json()['message'] ?? trans('global.something_wrong'),
                ], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => trans('global.something_wrong'),
                'exception' => $e->getMessage(),
            ], 500);
        }
    }

    public function changeEmail(Request $request)
    {
        // Prepare the data to be sent to the existing change email API

        $data = [
            'token' => $request->input('token'),
            'email' => $request->input('email'),
            'otp_value' => $request->input('otp_value'),
        ];

        try {
            // Call the external API that handles the email change
            $response = Http::post(url('api/v1/changeEmail'), $data);

            // Check if the response is successful
            if ($response->successful()) {
                $responseData = $response->json();

                // Return both status and data part of the response
                return response()->json([
                    'status' => $responseData['status'],
                    'data' => $responseData['data'],
                ]);
            } else {
                return response()->json([
                    'status' => $response->status(),
                    'error' => $response->json()['message'] ?? trans('global.something_wrong'),
                ], $response->status());
            }
        } catch (\Exception $e) {
            // Handle exceptions and return an error response
            return response()->json([
                'status' => 500,
                'error' => trans('global.something_wrong'),
                'exception' => $e->getMessage(),
            ], 500);
        }
    }

    public function resendTokenEmailChange(Request $request)
    {
        // Validate incoming request
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',  // Ensure email is provided
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            $data = [
                'token' => $request->input('token'),
                'email' => $request->input('email'),
                'type' => 'email_reset',
            ];

            $response = Http::post(url('api/v1/ResendTokenEmailChange'), $data);

            if ($response->successful()) {
                $responseData = $response->json();

                return response()->json([
                    'status' => $responseData['status'],
                    'data' => $responseData['data'],
                ]);
            } else {
                return response()->json([
                    'status' => $response->status(),
                    'message' => $response->json()['message'] ?? 'Error while resending OTP',
                ], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred while resending OTP.',
                'exception' => $e->getMessage(),
            ], 500);
        }
    }

    public function checkMobileNumber(Request $request)
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'phone' => 'required|min:8|max:12',
            'phone_country' => 'required',
            'email' => 'nullable|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = AppUser::where('token', $request->input('token'))->first();

        if (! $user) {
            return response()->json([
                'status' => 404,
                'message' => trans('global.user_not_found'),
            ], 404);
        }

        $existingUser = AppUser::where('phone', $request->input('phone'))
            ->where('phone_country', $request->input('phone_country'))
            ->where('id', '!=', $user->id)
            ->withTrashed()
            ->first();

        if ($existingUser) {
            return response()->json([
                'status' => 400,
                'message' => trans('global.mobile_number_already_exists'),
            ], 400);
        }

        if ($user->phone == $request->input('phone') && $user->phone_country == $request->input('phone_country')) {
            return response()->json([
                'status' => 400,
                'message' => trans('global.mobile_number_same_as_current'),
            ], 400);
        }

        $data = [
            'token' => $request->input('token'),
            'phone' => $request->input('phone'),
            'phone_country' => $request->input('phone_country'),
            'email' => $request->input('email'),
        ];

        try {
            // Call external API
            $response = Http::post(url('api/v1/checkMobileNumber'), $data);

            if ($response->successful()) {
                $responseData = $response->json();

                // Return both status and data part of the response
                return response()->json([
                    'status' => $responseData['status'],
                    'data' => $responseData['data'],
                ]);
            } else {
                return response()->json([
                    'status' => $response->status(),
                    'error' => $response->json()['message'] ?? trans('global.something_wrong'),
                ], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => trans('global.something_wrong'),
                'exception' => $e->getMessage(),
            ], 500);
        }
    }

    public function changeMobileNumber(Request $request)
    {
        // Prepare the data to be sent to the existing change mobile number API
        $data = [
            'token' => $request->input('token'),
            'phone' => $request->input('phone'),
            'phone_country' => $request->input('phone_country'),
            'otp_value' => $request->input('otp_value'),
            'default_country' => $request->input('default_country'),
            'email' => $request->input('email'),
        ];

        try {
            // Call the external API that handles the mobile number change
            $response = Http::post(url('api/v1/changeMobileNumber'), $data);

            // Check if the response is successful
            if ($response->successful()) {
                $responseData = $response->json();

                // Return both status and data part of the response
                return response()->json([
                    'status' => $responseData['status'],
                    'data' => $responseData['data'],
                ]);
            } else {
                // Handle failure response
                return response()->json([
                    'status' => $response->status(),
                    'error' => $response->json()['message'] ?? trans('global.something_wrong'),
                ], $response->status());
            }
        } catch (\Exception $e) {
            // Handle exceptions and return an error response
            return response()->json([
                'status' => 500,
                'error' => trans('global.something_wrong'),
                'exception' => $e->getMessage(),
            ], 500);
        }
    }

    public function updatePassword(Request $request)
    {

        $data = [
            'token' => $request->input('token'),
            'old_password' => $request->input('old_password'),
            'new_password' => $request->input('new_password'),
            'conf_new_password' => $request->input('conf_new_password'),
        ];

        try {
            $response = Http::post(url('api/v1/updatePassword'), $data);

            if ($response->successful()) {
                $responseData = $response->json();

                return response()->json([
                    'status' => $responseData['status'],
                    'data' => $responseData['data'] ?? null,
                ]);
            } else {
                return response()->json([
                    'status' => $response->status(),
                    'error' => $response->json()['error'] ?? trans('global.something_wrong'),
                ], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => trans('global.something_wrong'),
                'exception' => $e->getMessage(),
            ], 500);
        }
    }
}
