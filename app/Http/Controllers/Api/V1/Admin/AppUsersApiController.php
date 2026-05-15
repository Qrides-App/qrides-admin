<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\EmailTrait;
use App\Http\Controllers\Traits\FirestoreTrait;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Controllers\Traits\MiscellaneousTrait;
use App\Http\Controllers\Traits\NotificationTrait;
use App\Http\Controllers\Traits\OTPTrait;
use App\Http\Controllers\Traits\PushNotificationTrait;
use App\Http\Controllers\Traits\ResponseTrait;
use App\Http\Controllers\Traits\SMSTrait;
use App\Http\Controllers\Traits\UserWalletTrait;
use App\Http\Controllers\Traits\VendorWalletTrait;
use App\Http\Requests\StoreAppUserRequest;
use App\Http\Requests\UpdateAppUserRequest;
use App\Http\Resources\Admin\AppUserResource;
use App\Models\AppUser;
use App\Models\AppUserMeta;
use App\Models\BookingExtension;
use App\Models\City;
use App\Models\DriverRechargeInvoice;
use App\Models\DriverRechargePlan;
use App\Models\GeneralSetting;
use App\Models\HireBooking;
use App\Models\Media;
use App\Models\PendingAppUserRegistration;
use App\Models\Modern\Item;
use App\Models\Modern\ItemVehicle;
use App\Models\Payout;
use App\Models\PayoutMethod;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Models\Wallet;
use App\Services\RechargeBillingService;
use Auth;
use Carbon\Carbon;
use Gate;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpFoundation\Response;
use Validator;

class AppUsersApiController extends Controller
{
    use EmailTrait, FirestoreTrait, MediaUploadingTrait, MiscellaneousTrait, NotificationTrait, OTPTrait, PushNotificationTrait, ResponseTrait, SMSTrait, UserWalletTrait, VendorWalletTrait;

    public function index()
    {
        abort_if(Gate::denies('app_user_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return new AppUserResource(AppUser::with(['package'])->get());
    }

    public function store(StoreAppUserRequest $request)
    {
        $data = $request->all();
        $data['password'] = Hash::make($data['password']);
        $appUser = AppUser::create($data);

        if ($request->input('profile_image', false)) {
            $appUser->addMedia(storage_path('tmp/uploads/'.basename($request->input('profile_image'))))->toMediaCollection('profile_image');
        }

        return (new AppUserResource($appUser))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(AppUser $appUser)
    {
        abort_if(Gate::denies('app_user_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return new AppUserResource($appUser->load(['package']));
    }

    public function update(UpdateAppUserRequest $request, AppUser $appUser)
    {
        $data = $request->all();
        if ($data['password']) {
            $data['password'] = Hash::make($data['password']);
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

        return (new AppUserResource($appUser))
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }
    // //////////// API ////////////

    public function userRegister(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'phone' => ['required', 'numeric', 'min:9'],
                'email' => ['required', 'email'],
                'first_name' => ['required'],
                'phone_country' => ['required'],
                'user_type' => ['required'],
            ]);

            if ($validator->fails()) {

                return $this->errorComputing($validator);
            }
            $email = strtolower($request->email);
            $existingUsers = AppUser::withTrashed()
                ->where(function ($query) use ($request, $email) {
                    $query->where(function ($phoneQuery) use ($request) {
                        $phoneQuery->where('phone', $request->phone)
                            ->where('phone_country', $request->phone_country);
                    })->orWhere('email', $email);
                })
                ->get();

            $recoverableUser = null;
            foreach ($existingUsers as $existingUser) {
                if ($this->isRecoverablePendingSignup($existingUser, $request, $email)) {
                    $recoverableUser = $existingUser;
                    continue;
                }

                return $this->errorResponse(409, trans('global.user_alredy_exist'));
            }

            if ($recoverableUser) {
                $this->convertRecoverableSignupToPending($recoverableUser, $request, $email);
            }

            {
                $pendingConflict = PendingAppUserRegistration::query()
                    ->where(function ($query) use ($request, $email) {
                        $query->where(function ($phoneQuery) use ($request) {
                            $phoneQuery->where('phone', $request->phone)
                                ->where('phone_country', $request->phone_country);
                        })->orWhere('email', $email);
                    })
                    ->first();

                if ($pendingConflict && $pendingConflict->expires_at && $pendingConflict->expires_at->lt(Carbon::now())) {
                    $pendingConflict->delete();
                    $pendingConflict = null;
                }

                if (
                    $pendingConflict &&
                    (
                        $pendingConflict->phone !== $request->phone ||
                        $pendingConflict->phone_country !== $request->phone_country ||
                        strtolower((string) $pendingConflict->email) !== $email
                    )
                ) {
                    return $this->errorResponse(409, trans('global.user_alredy_exist'));
                }

                $pendingToken = $pendingConflict?->token ?: Str::random(120);
                $pending = PendingAppUserRegistration::updateOrCreate(
                    [
                        'phone' => $request->phone,
                        'phone_country' => $request->phone_country,
                    ],
                    [
                        'first_name' => $request->first_name,
                        'last_name' => $request->last_name,
                        'email' => $email,
                        'default_country' => $request->default_country,
                        'user_type' => $request->user_type,
                        'fcm' => $request->fcm,
                        'device_id' => $request->device_id,
                        'token' => $pendingToken,
                        'expires_at' => Carbon::now()->addMinutes(10),
                    ]
                );

                $otp = $this->generateOtp($request->phone, $request->phone_country);
                $otpSendResult = $this->sendPendingRegistrationOtpNotifications($pending, $otp);
                if (! $otpSendResult['ok']) {
                    return $this->errorResponse(422, $otpSendResult['error']);
                }
                $pending->update([
                    'otp_channel' => $otpSendResult['channel'],
                    'otp_sent_at' => Carbon::now(),
                    'expires_at' => Carbon::now()->addMinutes(10),
                ]);

                $responseData = [
                    'first_name' => $pending->first_name,
                    'last_name' => $pending->last_name,
                    'email' => $pending->email,
                    'phone' => $pending->phone,
                    'phone_country' => $pending->phone_country,
                    'default_country' => $pending->default_country,
                    'user_type' => $pending->user_type,
                    'token' => $pending->token,
                    'otp_value' => '',
                    'reset_token' => '',
                    'status' => 'pending',
                    'otp_delivery_channel' => $otpSendResult['channel'],
                    'otp_target_masked' => $otpSendResult['target_masked'],
                    'otp_delivery_hint' => $otpSendResult['hint'],
                ];

                return $this->successResponse(200, trans('global.OTP_sent_successfully'), $responseData);
            }
        } catch (\Exception $e) {
            return $this->errorResponse(401, trans('global.something_wrong'));
        }
    }

    public function generateOtp($phoneNumber, $countryCode)
    {

        DB::table('app_user_otps')
            ->where('phone', $phoneNumber)
            ->where('country_code', $countryCode)
            ->delete();

        $otp = $this->createOTP();

        $expiresAt = Carbon::now()->addMinutes(10);

        DB::table('app_user_otps')->insert([
            'phone' => $phoneNumber,
            'country_code' => $countryCode,
            'otp_code' => $otp,
            'created_at' => Carbon::now(),
            'expires_at' => $expiresAt,
        ]);

        $this->logOtpForTesting('phone', $countryCode.$phoneNumber, $otp);

        return $otp;
    }

    protected function isMobileOtpEnabled(): bool
    {
        $raw = GeneralSetting::getMetaValue('mobile_otp_enabled');

        if ($raw === null || $raw === '') {
            return true;
        }

        return filter_var($raw, FILTER_VALIDATE_BOOLEAN);
    }

    protected function isEmailOtpEnabled(): bool
    {
        $raw = GeneralSetting::getMetaValue('email_otp_enabled');

        if ($raw === null || $raw === '') {
            return false;
        }

        return filter_var($raw, FILTER_VALIDATE_BOOLEAN);
    }

    protected function resolveAuthOtpChannels(): array
    {
        $mobile = $this->isMobileOtpEnabled();
        $email = $this->isEmailOtpEnabled();

        if (! $mobile && ! $email) {
            $mobile = true;
        }

        return [
            'mobile' => $mobile,
            'email' => $email,
        ];
    }

    protected function maskEmailForOtp(?string $email): string
    {
        $email = trim((string) $email);
        if ($email === '' || ! str_contains($email, '@')) {
            return '';
        }

        [$local, $domain] = explode('@', $email, 2);
        $visible = substr($local, 0, 1);

        return $visible.str_repeat('*', max(strlen($local) - 1, 1)).'@'.$domain;
    }

    protected function buildOtpDeliveryMeta($user, array $channels): array
    {
        if ($channels['email'] && ! $channels['mobile']) {
            $maskedTarget = $this->maskEmailForOtp($user->email);

            return [
                'channel' => 'email',
                'target_masked' => $maskedTarget,
                'hint' => $maskedTarget !== ''
                    ? 'Enter OTP sent to your registered email '.$maskedTarget
                    : 'Enter OTP sent to your registered email',
            ];
        }

        if ($channels['email'] && $channels['mobile']) {
            $maskedTarget = $this->maskEmailForOtp($user->email);

            return [
                'channel' => 'email_and_mobile',
                'target_masked' => $maskedTarget !== '' ? $maskedTarget : (string) $user->phone,
                'hint' => $maskedTarget !== ''
                    ? 'Enter OTP sent to your email '.$maskedTarget.' and phone number'
                    : 'Enter OTP sent to your phone number',
            ];
        }

        return [
            'channel' => 'mobile',
            'target_masked' => (string) $user->phone,
            'hint' => 'Enter OTP sent to your phone number',
        ];
    }

    protected function sendPendingRegistrationOtpNotifications(PendingAppUserRegistration $pending, string $otp): array
    {
        $channels = $this->resolveAuthOtpChannels();
        $deliveryMeta = $this->buildOtpDeliveryMeta($pending, $channels);

        if ($channels['email'] && empty($pending->email) && ! $channels['mobile']) {
            return [
                'ok' => false,
                'error' => 'Email OTP is enabled, but this signup has no registered email address.',
            ];
        }

        $delivered = false;
        $errors = [];
        $appName = trim((string) (GeneralSetting::getMetaValue('general_name') ?: config('app.name')));
        $subject = ($appName !== '' ? $appName : 'QRides').' OTP Verification';
        $smsMessage = 'Your OTP is '.$otp.'. It is valid for 10 minutes.';

        if ($channels['mobile']) {
            try {
                $phone = ltrim((string) $pending->phone_country, '+').(string) $pending->phone;
                $this->sendSMS($subject, $smsMessage, $phone);
                $delivered = true;
            } catch (\Throwable $exception) {
                $errors[] = $exception->getMessage();
            }
        }

        if ($channels['email'] && ! empty($pending->email)) {
            $body = '
                <h2 style="margin:0 0 16px;">OTP Verification</h2>
                <p>Hello '.e(trim((string) ($pending->first_name ?: 'User'))).',</p>
                <p>Your one time password is:</p>
                <div style="font-size:24px;font-weight:700;letter-spacing:4px;margin:16px 0;">'.e($otp).'</div>
                <p>This OTP is valid for 10 minutes.</p>
                <p>If you did not request this, please ignore this email.</p>
            ';

            $mailResult = $this->sendMail($subject, $body, $pending->email);
            if (str_starts_with($mailResult, 'Mail sent successfully')) {
                $delivered = true;
            } else {
                $errors[] = $mailResult;
            }
        }

        if (! $delivered) {
            return [
                'ok' => false,
                'error' => count($errors) > 0
                    ? 'OTP delivery failed. '.implode(' ', $errors)
                    : 'OTP delivery failed. Please check SMS/SMTP configuration.',
            ];
        }

        return [
            'ok' => true,
            'channel' => $deliveryMeta['channel'],
            'target_masked' => $deliveryMeta['target_masked'],
            'hint' => $deliveryMeta['hint'],
        ];
    }

    protected function createRealUserFromPendingSignup(PendingAppUserRegistration $pending, string $otpChannel): AppUser
    {
        $customerData = [
            'phone' => $pending->phone,
            'email' => strtolower((string) $pending->email),
            'first_name' => $pending->first_name,
            'last_name' => $pending->last_name,
            'phone_country' => $pending->phone_country,
            'fcm' => $pending->fcm,
            'status' => 1,
            'default_country' => $pending->default_country,
            'user_type' => $pending->user_type,
            'token' => Str::random(120),
            'device_id' => $pending->device_id,
            'email_verify' => in_array($otpChannel, ['email', 'email_and_mobile'], true) ? 1 : 0,
            'phone_verify' => in_array($otpChannel, ['mobile', 'email_and_mobile'], true) ? 1 : 0,
            'verified' => 1,
            'otp_value' => '0',
            'reset_token' => '0',
        ];

        if (Schema::hasColumn('app_users', 'otp_expires_at')) {
            $customerData['otp_expires_at'] = null;
        }

        $customer = AppUser::create($customerData);

        if ($pending->user_type === 'driver') {
            $firestoreData = $this->generateDriverFirestoreData($customer);
            $firestoreDoc = $this->storeDriverInFirestore($firestoreData);
            $firestoreDocId = basename($firestoreDoc);
            $customer->update(['firestore_id' => $firestoreDocId]);
            $customer['firestore_id'] = $firestoreDocId;
        }

        try {
            $valuesArray = $customer->only(['first_name', 'last_name', 'email']);
            $valuesArray['phone'] = $customer->phone_country.$customer->phone;
            $settings = GeneralSetting::whereIn('meta_key', ['general_email'])->get()->keyBy('meta_key');
            $generalEmail = $settings['general_email']->meta_value ?? null;
            $valuesArray['support_email'] = $generalEmail;
            $this->sendAllNotifications($valuesArray, $customer->id, 1);
        } catch (\Throwable $exception) {
            Log::warning('Welcome notification failed after OTP verification.', [
                'phone' => $pending->phone,
                'phone_country' => $pending->phone_country,
                'message' => $exception->getMessage(),
            ]);
        }

        return $customer;
    }

    protected function isRecoverablePendingSignup(AppUser $user, Request $request, string $normalizedEmail): bool
    {
        if ($user->trashed()) {
            return false;
        }

        if ((string) $user->phone !== (string) $request->phone ||
            (string) $user->phone_country !== (string) $request->phone_country ||
            strtolower((string) $user->email) !== $normalizedEmail ||
            (string) $user->user_type !== (string) $request->user_type) {
            return false;
        }

        if ((string) $user->status === '1' ||
            (string) ($user->verified ?? '0') === '1' ||
            (string) ($user->phone_verify ?? '0') === '1' ||
            (string) ($user->email_verify ?? '0') === '1') {
            return false;
        }

        if (! empty($user->token)) {
            return false;
        }

        return ! $user->items()->exists()
            && ! $user->metadata()->exists()
            && ! $user->bookings()->exists()
            && ! $user->hostBookings()->exists()
            && ! $user->wallets()->exists()
            && ! $user->vendorWallets()->exists()
            && ! $user->supportTickets()->exists()
            && ! $user->payouts()->exists();
    }

    protected function convertRecoverableSignupToPending(AppUser $user, Request $request, string $normalizedEmail): PendingAppUserRegistration
    {
        return DB::transaction(function () use ($user, $request, $normalizedEmail) {
            $pending = PendingAppUserRegistration::updateOrCreate(
                [
                    'phone' => $request->phone,
                    'phone_country' => $request->phone_country,
                ],
                [
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'email' => $normalizedEmail,
                    'default_country' => $request->default_country,
                    'user_type' => $request->user_type,
                    'fcm' => $request->fcm,
                    'device_id' => $request->device_id,
                    'token' => $user->reset_token ?: Str::random(120),
                    'otp_channel' => null,
                    'otp_sent_at' => null,
                    'expires_at' => Carbon::now()->addMinutes(10),
                ]
            );

            $user->forceDelete();

            return $pending;
        });
    }

    protected function sendAuthOtpNotifications(AppUser $user, string $otp, int $smsTemplateId = 2): array
    {
        $channels = $this->resolveAuthOtpChannels();
        $deliveryMeta = $this->buildOtpDeliveryMeta($user, $channels);

        if ($channels['email'] && empty($user->email) && ! $channels['mobile']) {
            return [
                'ok' => false,
                'error' => 'Email OTP is enabled, but this account has no registered email address.',
            ];
        }

        $delivered = false;
        $errors = [];

        if ($channels['mobile']) {
            try {
                $this->sendAllNotifications([
                    'OTP' => $otp,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                ], $user->id, $smsTemplateId);
                $delivered = true;
            } catch (\Throwable $exception) {
                $errors[] = $exception->getMessage();
            }
        }

        if ($channels['email'] && ! empty($user->email)) {
            $appName = trim((string) (GeneralSetting::getMetaValue('general_name') ?: config('app.name')));
            $subject = ($appName !== '' ? $appName : 'QRides').' OTP Verification';
            $body = '
                <h2 style="margin:0 0 16px;">OTP Verification</h2>
                <p>Hello '.e(trim((string) ($user->first_name ?: 'User'))).',</p>
                <p>Your one time password is:</p>
                <div style="font-size:24px;font-weight:700;letter-spacing:4px;margin:16px 0;">'.e($otp).'</div>
                <p>This OTP is valid for 10 minutes.</p>
                <p>If you did not request this, please ignore this email.</p>
            ';

            $mailResult = $this->sendMail($subject, $body, $user->email);
            if (str_starts_with($mailResult, 'Mail sent successfully')) {
                $delivered = true;
            } else {
                $errors[] = $mailResult;
            }
        }

        if (! $delivered) {
            return [
                'ok' => false,
                'error' => count($errors) > 0
                    ? 'OTP delivery failed. '.implode(' ', $errors)
                    : 'OTP delivery failed. Please check SMS/SMTP configuration.',
            ];
        }

        return [
            'ok' => true,
            'channel' => $deliveryMeta['channel'],
            'target_masked' => $deliveryMeta['target_masked'],
            'hint' => $deliveryMeta['hint'],
        ];
    }

    protected function logOtpForTesting(string $channel, string $recipient, string $otp): void
    {
        if (! $this->shouldLogOtpForTesting()) {
            return;
        }

        \Log::info('OTP generated for testing', [
            'channel' => $channel,
            'recipient' => $recipient,
            'otp' => $otp,
        ]);
    }

    protected function shouldLogOtpForTesting(): bool
    {
        if (filter_var(env('OTP_LOGGING_ENABLED', false), FILTER_VALIDATE_BOOL)) {
            return true;
        }

        return app()->environment(['local', 'development', 'testing']);
    }

    protected function maskPhoneForLogs(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return 'unknown';
        }

        if (strlen($digits) <= 4) {
            return str_repeat('*', strlen($digits));
        }

        return str_repeat('*', max(strlen($digits) - 4, 0)).substr($digits, -4);
    }

    protected function logMobileLoginEvent(string $message, array $context = [], string $level = 'info'): void
    {
        if (isset($context['phone'])) {
            $context['phone_masked'] = $this->maskPhoneForLogs((string) $context['phone']);
            unset($context['phone']);
        }

        Log::log($level, $message, $context);
    }

    protected function createPlaceholderDriverItem(AppUser $customer, int $module): Item
    {
        $driverName = trim(implode(' ', array_filter([
            $customer->first_name ?? null,
            $customer->last_name ?? null,
        ])));

        $data = [
            'userid_id' => $customer->id,
            'token' => strtoupper(Str::random(20)),
            'title' => ($driverName !== '' ? $driverName : 'Captain').' Vehicle',
        ];

        if (Schema::hasColumn('rental_items', 'module')) {
            $data['module'] = $module;
        }

        if (Schema::hasColumn('rental_items', 'status')) {
            $data['status'] = 0;
        }

        if (Schema::hasColumn('rental_items', 'is_verified')) {
            $data['is_verified'] = 0;
        }

        return Item::create($data);
    }

    protected function prepareAuthenticatedUserPayload(AppUser $customer, Request $request): AppUser
    {
        $module = $this->getModuleIdOrDefault($request);
        $remainingItems = $this->checkRemainingItems($customer->id, $module);
        $customer['remaining_items'] = $remainingItems ?? 0;

        $isDriverFlow = $request->user_type === 'driver' || $customer->user_type === 'driver';
        if (! $isDriverFlow) {
            return $customer;
        }

        $item = Item::where('userid_id', $customer->id)->first();
        if (! $item) {
            $item = $this->createPlaceholderDriverItem($customer, $module);
        }

        if ($item) {
            $customer['item_id'] = $item->id;
            $customer['item_type_id'] = $item->item_type_id;
        }

        $docunmentsFields = [
            'driving_licence_front_status',
            'driving_licence_back_status',
            'aadhaar_front_status',
            'aadhaar_back_status',
            'pan_card_status',
            'vehicle_insurance_doc_status',
        ];

        $metaStatuses = AppUserMeta::where('user_id', $customer->id)
            ->whereIn('meta_key', $docunmentsFields)
            ->pluck('meta_value', 'meta_key');

        $statuses = [];
        foreach ($docunmentsFields as $field) {
            $statuses[] = $metaStatuses[$field] ?? '';
        }

        if (in_array('rejected', $statuses, true)) {
            $customer['verification_document_status'] = 'rejected';
        } elseif (count(array_filter($statuses, fn ($status) => $status !== 'approved')) > 0) {
            $customer['verification_document_status'] = 'pending';
        } else {
            $customer['verification_document_status'] = 'approved';
        }

        return $customer;
    }

    public function validateOtpFromDB($phoneNumber, $countryCode, $inputOtp, bool $consumeOtp = true)
    {

        $otpRecord = DB::table('app_user_otps')
            ->where('phone', $phoneNumber)
            ->where('country_code', $countryCode)
            ->orderByDesc('created_at')
            ->first();

        if (! $otpRecord) {
            return [
                'status' => trans('global.failed'),
                'message' => trans('global.noOTP_recordFound'),
            ];
        }

        $currentTime = Carbon::now();
        $expiresAt = Carbon::parse($otpRecord->expires_at);

        if ($currentTime->greaterThanOrEqualTo($expiresAt)) {
            return [
                'status' => trans('global.failed'),
                'message' => trans('global.OTPhas_expired'),
            ];
        }

        if ($otpRecord->otp_code === $inputOtp) {

            if ($consumeOtp) {
                DB::table('app_user_otps')
                    ->where('id', $otpRecord->id)
                    ->delete();
            }

            return [
                'status' => trans('global.success'),
                'message' => trans('global.OTP_varified'),
                'otp_id' => $otpRecord->id,
            ];
        } else {
            return [
                'status' => trans('global.failed'),
                'message' => trans('global.Incorrect_OTP'),
            ];
        }
    }

    protected function promotePendingRegistration(
        PendingAppUserRegistration $pending,
        Request $request,
        ?int $otpRecordId = null
    ): AppUser {
        return DB::transaction(function () use ($pending, $request, $otpRecordId) {
            $customer = $this->createRealUserFromPendingSignup(
                $pending,
                $pending->otp_channel ?: 'mobile'
            );

            $pending->delete();

            if ($otpRecordId) {
                DB::table('app_user_otps')
                    ->where('id', $otpRecordId)
                    ->delete();
            }

            return $this->prepareAuthenticatedUserPayload($customer->fresh(), $request);
        });
    }

    public function otpVerification(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone' => ['required', 'numeric', 'min:9'],
                'otp_value' => ['required'],
                'phone_country' => ['required'],
            ]);

            if ($validator->fails()) {
                return $this->errorComputing($validator);
            }

            $customer = AppUser::where('phone', $request->phone)
                ->where('phone_country', $request->phone_country)
                ->first();

            if ($customer) {
                $resultOtp = $this->validateOtpFromDB($request->phone, $request->phone_country, $request->otp_value);
                if ($resultOtp['status'] !== 'success') {
                    return $this->errorResponse(401, trans('global.Wrong_OTP'));
                }

                $customerColumns = array_flip(Schema::getColumnListing('app_users'));
                $customerUpdates = [
                    'otp_value' => '0',
                    'email_verify' => '1',
                    'phone_verify' => '1',
                    'status' => '1',
                ];
                if (isset($customerColumns['verified'])) {
                    $customerUpdates['verified'] = '1';
                }
                $customer->update($customerUpdates);

                $customer = $this->prepareAuthenticatedUserPayload($customer->fresh(), $request);

                return $this->successResponse(200, trans('global.Login_Sucessfully'), $customer);
            }

            $pending = PendingAppUserRegistration::where('phone', $request->phone)
                ->where('phone_country', $request->phone_country)
                ->first();

            if (! $pending) {
                return $this->errorResponse(404, trans('global.User_not_register'));
            }

            if ($pending->expires_at && $pending->expires_at->lt(Carbon::now())) {
                $pending->delete();

                return $this->errorResponse(401, trans('global.OTPhas_expired'));
            }

            $resultOtp = $this->validateOtpFromDB(
                $request->phone,
                $request->phone_country,
                $request->otp_value,
                false
            );
            if ($resultOtp['status'] !== 'success') {
                return $this->errorResponse(401, trans('global.Wrong_OTP'));
            }

            $customer = $this->promotePendingRegistration(
                $pending,
                $request,
                $resultOtp['otp_id'] ?? null
            );

            return $this->successResponse(200, trans('global.Login_Sucessfully'), $customer);
        } catch (\Exception $e) {
            return $this->errorResponse(401, trans('global.something_wrong'));
        }
    }

    public function userLogout(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                'token' => ['required'],
                'id' => ['required'],
            ]);

            if ($validator->fails()) {
                return $this->errorComputing($validator);
            }
            if (AppUser::where('token', $request->token)->exists()) {
                AppUser::where('token', $request->token)->update(['token' => '']);

                return $this->successResponse(200, trans('global.Logout_Sucessfully'));
            }
        } catch (\Exception $e) {
            return $this->errorResponse(401, trans('global.something_wrong'));
        }
    }

    public function userLogin(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone' => ['required', 'numeric', 'min:9'],
                'password' => ['required'],
                'phone_country' => ['required'],
            ]);

            if ($validator->fails()) {
                return $this->errorComputing($validator);
            }
            $data = [
                'phone' => $request->phone,
                'password' => $request->password,
                'phone_country' => $request->phone_country,

            ];

            if (Auth::guard('appUser')->attempt($data)) {
                $otp = $this->createOTP();
                AppUser::where('phone', $request->phone)->update(['otp_value' => $otp, 'token' => '']);
                $customer = AppUser::where('phone', $request->phone)->first();
                unset($customer['token']);

                return $this->successResponse(200, trans('global.Login_Sucessfully'), $customer);
            } else {
                return $this->errorResponse(401, trans('global.something_wrong'));
            }
        } catch (\Exception $e) {
            return $this->errorResponse(401, trans('global.something_wrong'));
        }
    }

    public function userEmailLogin(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                'email' => ['required', 'email'],
                'password' => ['required'],
            ]);

            if ($validator->fails()) {
                return $this->errorComputing($validator);
            }

            $data = [
                'email' => strtolower($request->email),
                'password' => $request->password,
            ];

            if (Auth::guard('appUser')->attempt($data)) {
                $customer = AppUser::where('email', $request->email)->first();

                if ($customer->status != 1) {
                    $otp = $this->generateOtp($customer->phone, $customer->phone_country);
                    $this->sendAllNotifications(['OTP' => $otp], $customer->id, 2);
                    $customer['reset_token'] = '';

                    return $this->successResponse(200, trans('global.account_inactive'), $customer);
                }

                $token = Str::random(120);
                $loginUpdate = [
                    'token' => $token,
                    'reset_token' => 0,
                ];
                if (Schema::hasColumn('app_users', 'otp_expires_at')) {
                    $loginUpdate['otp_expires_at'] = null;
                }
                $customer->update($loginUpdate);

                $mediaItem = Media::where('model_id', $customer->id)
                    ->where('model_type', 'App\Models\AppUser')
                    ->where('collection_name', 'identity_image')
                    ->first();

                $domain = env('APP_URL');
                $imageUrl = $mediaItem ? asset($domain.'/storage/app/public/'.$mediaItem->id.'/'.$mediaItem->file_name) : '';
                $customer['identity_image'] = $imageUrl;

                $module = $this->getModuleIdOrDefault($request);
                $remainingItems = $this->checkRemainingItems($customer->id, $module);

                if ($remainingItems) {
                    $customer['remaining_items'] = $remainingItems;
                } else {
                    $customer['remaining_items'] = 0;
                }

                $firebaseMeta = $customer->metadata->where('meta_key', 'firebase_auth')->first();
                if (! $firebaseMeta) {

                    $firebasePassword = Str::random(16);
                    $apiKey = (string) config('services.firebase_web.apiKey', '');

                    if ($apiKey === '') {
                        Log::warning('Firebase web API key is missing while provisioning firebase_auth metadata.', [
                            'app_user_id' => $customer->id,
                        ]);
                        $customer['firebase_auth'] = 0;
                        return $this->successResponse(200, trans('global.Login_Sucessfully'), $customer);
                    }

                    $userFirebase = $this->createFirebaseUser($request->email, $firebasePassword, $apiKey);

                    if (isset($userFirebase['success']) && ! $userFirebase['success']) {

                        $result = AppUserMeta::updateOrCreate(
                            [
                                'meta_key' => 'firebase_auth',
                                'user_id' => $customer->id,
                            ],
                            [
                                'meta_value' => 0,
                            ]
                        );
                        $customer['firebase_auth'] = 0;
                    }

                    if (isset($userFirebase['success']) && $userFirebase['success']) {
                        $result = AppUserMeta::updateOrCreate(
                            [
                                'meta_key' => 'firebase_auth',
                                'user_id' => $customer->id,
                            ],
                            [
                                'meta_value' => 1,
                            ]
                        );
                        $customer['firebase_auth'] = 1;
                    }
                } else {
                    $customer['firebase_auth'] = $firebaseMeta->meta_value;
                }

                return $this->successResponse(200, trans('global.Login_Sucessfully'), $customer);
            } else {
                return $this->errorResponse(401, trans('global.user_not_exist'));
            }
            // try {
        } catch (\Exception $e) {
            return $this->errorResponse(401, trans('global.something_wrong'));
        }
    }

    public function userMobileLogin(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'numeric', 'min:9'],
            'otp_value' => ['required'],
            'phone_country' => ['required'],
            'user_type' => ['required'],
        ]);

        if ($validator->fails()) {
            $this->logMobileLoginEvent('Mobile login validation failed.', [
                'phone' => $request->phone,
                'phone_country' => $request->phone_country,
                'errors' => $validator->errors()->toArray(),
            ], 'warning');
            return $this->errorComputing($validator);
        }

        $this->logMobileLoginEvent('Mobile login attempt received.', [
            'phone' => $request->phone,
            'phone_country' => $request->phone_country,
            'user_type' => $request->user_type,
        ]);

        $customer = AppUser::where('phone', $request->phone)
            ->where('phone_country', $request->phone_country)
            ->where('user_type', $request->user_type)
            ->first();
        if ($customer) {
            $resultOtp = $this->validateOtpFromDB($request->phone, $request->phone_country, $request->otp_value);
            if ($resultOtp['status'] !== 'success') {
                $this->logMobileLoginEvent('Mobile login OTP validation failed.', [
                    'phone' => $request->phone,
                    'phone_country' => $request->phone_country,
                    'user_id' => optional($customer)->id,
                    'reason' => $resultOtp['message'] ?? 'unknown',
                ], 'warning');

                return $this->errorResponse(401, trans('global.Wrong_OTP'));
            }

            $token = Str::random(120);
            if ($customer->status != 1) {
                $this->logMobileLoginEvent('Mobile login blocked because account is inactive.', [
                    'phone' => $request->phone,
                    'phone_country' => $request->phone_country,
                    'user_id' => $customer->id,
                ], 'warning');
                return $this->successResponse(200, trans('global.account_inactive'), $customer);
            }

            $loginUpdate = [
                'token' => $token,
                'reset_token' => 0,
            ];
            if (Schema::hasColumn('app_users', 'otp_expires_at')) {
                $loginUpdate['otp_expires_at'] = null;
            }
            $customer->update($loginUpdate);
            $customer = $this->prepareAuthenticatedUserPayload($customer->fresh(), $request);

            $this->logMobileLoginEvent('Mobile login completed successfully.', [
                'phone' => $request->phone,
                'phone_country' => $request->phone_country,
                'user_id' => $customer->id,
                'user_type' => $customer->user_type,
            ]);

            return $this->successResponse(200, trans('global.Login_Sucessfully'), $customer);
        }

        $pending = PendingAppUserRegistration::where('phone', $request->phone)
            ->where('phone_country', $request->phone_country)
            ->where('user_type', $request->user_type)
            ->first();

        if ($pending) {
            if ($pending->expires_at && $pending->expires_at->lt(Carbon::now())) {
                $pending->delete();

                return $this->errorResponse(401, trans('global.OTPhas_expired'));
            }

            $resultOtp = $this->validateOtpFromDB(
                $request->phone,
                $request->phone_country,
                $request->otp_value,
                false
            );
            if ($resultOtp['status'] !== 'success') {
                $this->logMobileLoginEvent('Pending signup OTP validation failed during mobile login.', [
                    'phone' => $request->phone,
                    'phone_country' => $request->phone_country,
                    'reason' => $resultOtp['message'] ?? 'unknown',
                ], 'warning');

                return $this->errorResponse(401, trans('global.Wrong_OTP'));
            }

            $customer = $this->promotePendingRegistration(
                $pending,
                $request,
                $resultOtp['otp_id'] ?? null
            );

            $this->logMobileLoginEvent('Pending signup promoted during mobile login.', [
                'phone' => $request->phone,
                'phone_country' => $request->phone_country,
                'user_id' => $customer->id,
                'user_type' => $customer->user_type,
            ]);

            return $this->successResponse(200, trans('global.Login_Sucessfully'), $customer);
        }

        $this->logMobileLoginEvent('Mobile login rejected because user was not found.', [
            'phone' => $request->phone,
            'phone_country' => $request->phone_country,
        ], 'warning');
        return $this->errorResponse(404, trans('global.User_not_register'));
    }

    public function sendMobileLoginOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|numeric',
            'phone_country' => 'required',
            'user_type' => 'required',
        ]);

        if ($validator->fails()) {
            $this->logMobileLoginEvent('Mobile login OTP request validation failed.', [
                'phone' => $request->phone,
                'phone_country' => $request->phone_country,
                'errors' => $validator->errors()->toArray(),
            ], 'warning');
            return $this->errorComputing($validator);
        }

        $this->logMobileLoginEvent('Mobile login OTP request received.', [
            'phone' => $request->phone,
            'phone_country' => $request->phone_country,
        ]);

        $user = AppUser::where('phone', $request->input('phone'))
            ->where('phone_country', $request->phone_country)
            ->where('user_type', $request->user_type)
            ->first();

        if (! $user) {
            $pending = PendingAppUserRegistration::where('phone', $request->phone)
                ->where('phone_country', $request->phone_country)
                ->where('user_type', $request->user_type)
                ->first();

            if (! $pending) {
                $this->logMobileLoginEvent('Mobile login OTP request rejected because user was not found.', [
                    'phone' => $request->phone,
                    'phone_country' => $request->phone_country,
                ], 'warning');
                return $this->addErrorResponse(400, trans('global.User_not_found'), '');
            }

            if ($pending->expires_at && $pending->expires_at->lt(Carbon::now())) {
                $pending->delete();

                return $this->addErrorResponse(410, trans('global.OTPhas_expired'), '');
            }

            $otp = $this->generateOtp($pending->phone, $pending->phone_country);
            $this->logMobileLoginEvent('Pending signup OTP generated from login request.', [
                'phone' => $pending->phone,
                'phone_country' => $pending->phone_country,
                'otp' => $otp,
            ]);

            $otpSendResult = $this->sendPendingRegistrationOtpNotifications($pending, $otp);
            if (! $otpSendResult['ok']) {
                return $this->addErrorResponse(422, $otpSendResult['error'], '');
            }

            $pending->update([
                'otp_channel' => $otpSendResult['channel'],
                'otp_sent_at' => Carbon::now(),
                'expires_at' => Carbon::now()->addMinutes(10),
            ]);

            $filteredPending = [
                'phone' => $pending->phone,
                'phone_country' => $pending->phone_country,
                'email' => $pending->email,
                'reset_token' => '',
                'token' => $pending->token,
                'otp_delivery_channel' => $otpSendResult['channel'],
                'otp_target_masked' => $otpSendResult['target_masked'],
                'otp_delivery_hint' => $otpSendResult['hint'],
                'status' => 'pending',
            ];

            return $this->successResponse(200, trans('global.OTP_sent_successfully'), $filteredPending);
        }

        $channels = $this->resolveAuthOtpChannels();
        if ($channels['email'] && ! $channels['mobile'] && empty($user->email)) {
            return $this->addErrorResponse(422, 'Email OTP is enabled, but no email is registered for this account.', '');
        }

        $otp = $this->generateOtp($user->phone, $user->phone_country);
        $this->logMobileLoginEvent('Mobile login OTP generated.', [
            'phone' => $user->phone,
            'phone_country' => $user->phone_country,
            'user_id' => $user->id,
            'otp' => $otp,
        ]);

        $otpSendResult = $this->sendAuthOtpNotifications($user, $otp, 2);
        if (! $otpSendResult['ok']) {
            Log::warning('Login OTP delivery failed.', [
                'user_id' => $user->id,
                'message' => $otpSendResult['error'],
            ]);

            return $this->addErrorResponse(422, $otpSendResult['error'], '');
        }

        $otpUpdate = [
            'reset_token' => $otp,
        ];
        if (Schema::hasColumn('app_users', 'otp_expires_at')) {
            $otpUpdate['otp_expires_at'] = Carbon::now()->addMinutes(5);
        }
        $user->update($otpUpdate);

        $this->logMobileLoginEvent('Mobile login OTP stored successfully.', [
            'phone' => $user->phone,
            'phone_country' => $user->phone_country,
            'user_id' => $user->id,
        ]);

        $user['reset_token'] = '';
        $filteredUser = $user->only([
            'phone',
            'phone_country',
            'email',
            'reset_token',
            'token',
        ]);
        $filteredUser['otp_delivery_channel'] = $otpSendResult['channel'];
        $filteredUser['otp_target_masked'] = $otpSendResult['target_masked'];
        $filteredUser['otp_delivery_hint'] = $otpSendResult['hint'];

        return $this->successResponse(200, trans('global.OTP_sent_successfully'), $filteredUser);
    }

    public function sendOnlyEmailLoginOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->errorComputing($validator);
        }

        //    try {

        $user = AppUser::where('email', $request->input('email'))->first();

        if (! $user) {
            return $this->addErrorResponse(400, trans('global.User_not_found'), '');
        }

        $otp = $this->generateOtp($user->phone, $user->phone_country);
        $valuesArray = ['OTP' => $otp, 'first_name' => $user->first_name, 'last_name' => $user->last_name];
        $template_id = 3;
        $this->sendAllNotifications($valuesArray, $user->id, $template_id);

        AppUser::where('email', $request->email)->update(['reset_token' => $otp, 'token' => '']);
        $responseData = [];
        $responseData['reset_token'] = '';

        return $this->successResponse(200, trans('global.Password_reset_OTP'), $responseData);
        try {
        } catch (\Exception $e) {
            return $this->addErrorResponse(500, trans('global.password_Set_error'), $e->getMessage());
        }
    }

    public function userOnlyEmailLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'otp_value' => ['required'],
        ]);

        if ($validator->fails()) {
            return $this->errorComputing($validator);
        }

        $user = AppUser::where('email', $request->email)->first();
        if (! $user) {
            return $this->addErrorResponse(400, trans('global.User_not_found'), '');
        }

        $resultOtp = $this->validateOtpFromDB($user->phone, $user->phone_country, $request->otp_value);
        if ($resultOtp['status'] === 'success') {
            $token = Str::random(120);

            if ($user->status != 1) {
                return $this->successResponse(200, trans('global.account_inactive'), $user);
            }

            $user->update(['token' => $token]);

            $module = $this->getModuleIdOrDefault($request);
            $remainingItems = $this->checkRemainingItems($user->id, $module);

            $user['remaining_items'] = $remainingItems ?? 0;

            return $this->successResponse(200, trans('global.Login_Sucessfully'), $user);
        } else {
            return $this->errorResponse(401, trans('global.Wrong_OTP'));
        }
    }

    public function socialLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'displayName' => 'nullable|string',
            'email' => 'nullable|email',
            'id' => 'required|string',
            'login_type' => 'required|in:google,apple',
            'profile_image' => 'nullable|string',
        ]);

        if (empty($request->input('email'))) {
            $temporaryEmailDomain = '@rideon.unibooker.app';
            $email = $request->input('id').$temporaryEmailDomain;
        } else {
            $email = strtolower($request->input('email'));
        }

        if ($validator->fails()) {
            return $this->errorComputing($validator);
        }
        try {
            $displayName = trim((string) $request->input('displayName', ''));
            $names = preg_split('/\s+/', $displayName, -1, PREG_SPLIT_NO_EMPTY);
            $firstName = $names[0] ?? 'User';
            $lastName = count($names) > 1 ? implode(' ', array_slice($names, 1)) : '';

            $socialId = $request->input('id');
            $loginType = $request->input('login_type');
            $user = null;
            $hasSocialIdColumn = Schema::hasColumn('app_users', 'social_id');
            $appUserColumns = array_flip(Schema::getColumnListing('app_users'));
            $hasSocialIdColumn = isset($appUserColumns['social_id']);
            $hasLoginTypeColumn = isset($appUserColumns['login_type']);
            $hasVerifiedColumn = isset($appUserColumns['verified']);

            DB::beginTransaction();

            if ($request->filled('email')) {
                $user = AppUser::where('email', $email)->withTrashed()->first();

                if (! is_null($user) && $user->trashed()) {
                    DB::rollBack();

                    return $this->addErrorResponse(400, trans('User has been block'), '');
                }
            }

            if (! $user && $hasSocialIdColumn && $request->filled('id')) {
                $user = AppUser::where('social_id', $request->input('id'))->first();
            }

            if ($user) {
                $userUpdates = [];

                if ($hasSocialIdColumn) {
                    $userUpdates['social_id'] = $socialId;
                }
                if ($hasLoginTypeColumn) {
                    $userUpdates['login_type'] = $loginType;
                }
                if ($hasVerifiedColumn && ! $user->verified) {
                    $userUpdates['verified'] = 1;
                }

                if (! empty($userUpdates)) {
                    $user->fill($userUpdates);
                    $user->save();
                }

                $customer = $this->generateAccessToken($user->email);
                $userIdForRemainingItems = $user->id;
            } else {
                $newUserData = [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'password' => Hash::make(Str::random(32)),
                    'verified' => 1,
                    'email_verify' => $request->filled('email') ? 1 : 0,
                    'status' => 1,
                    'user_type' => 'user',
                ];

                if ($hasLoginTypeColumn) {
                    $newUserData['login_type'] = $loginType;
                }

                if ($hasSocialIdColumn) {
                    $newUserData['social_id'] = $socialId;
                }

                $newUser = AppUser::create(array_intersect_key($newUserData, $appUserColumns));
                $imagePath = null;

                if ($request->filled('profile_image')) {
                    $imageData = @file_get_contents($request->input('profile_image'));

                    if ($imageData !== false) {
                        $imageName = Str::random(40).'.jpg';
                        $imagePath = 'profile_images/'.$imageName;

                        try {
                            $image = Image::make($imageData);
                            Storage::put($imagePath, $image->encode('jpg'));
                        } catch (\Exception $e) {
                            Log::error('Error processing profile image: '.$e->getMessage());
                            $imagePath = null;
                        }
                    }
                }

                if ($imagePath) {
                    $newUser->addMedia(storage_path('app/'.$imagePath))->toMediaCollection('profile_image');
                }

                $userIdForRemainingItems = $newUser->id;
                $customer = $this->generateAccessToken($email);
            }

            DB::commit();

            $module = $this->getModuleIdOrDefault($request);
            $remainingItems = $this->checkRemainingItems($userIdForRemainingItems, $module);
            $customer['remaining_items'] = $remainingItems ?? 0;

            return $this->successResponse(200, trans('global.Login_Sucessfully'), $customer);
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error('Social login failed', [
                'login_type' => $request->input('login_type'),
                'email' => $email,
                'social_id' => $request->input('id'),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->addErrorResponse(500, trans('global.ServerError_internal_server_error'), $e->getMessage());
        }
    }

    private function generateAccessToken($email)
    {
        $token = Str::random(120);

        $appUserColumns = array_flip(Schema::getColumnListing('app_users'));
        $updateData = array_intersect_key([
            'otp_value' => '0',
            'token' => $token,
            'verified' => '1',
        ], $appUserColumns);

        if (! empty($updateData)) {
            AppUser::where('email', $email)->update($updateData);
        }

        $customer = AppUser::where('email', $email)->first();

        return $customer;
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->errorComputing($validator);
        }

        //    try {

        $user = AppUser::where('email', $request->input('email'))->first();

        if (! $user) {
            return $this->addErrorResponse(400, trans('global.User_not_found'), '');
        }

        $otp = $this->generateOtp($user->phone, $user->phone_country);
        $valuesArray = ['OTP' => $otp, 'first_name' => $user->first_name, 'last_name' => $user->last_name];
        $template_id = 3;
        $this->sendAllNotifications($valuesArray, $user->id, $template_id);

        AppUser::where('email', $request->email)->update(['reset_token' => $otp, 'token' => '']);
        $responseData = [];
        $responseData['reset_token'] = '';

        return $this->successResponse(200, trans('global.Password_reset_OTP'), $responseData);
        try {
        } catch (\Exception $e) {
            return $this->addErrorResponse(500, trans('global.password_Set_error'), $e->getMessage());
        }
    }

    public function verifyResetToken(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'reset_token' => ['required'],
            ]);

            if ($validator->fails()) {
                return $this->errorComputing($validator);
            }

            $user = AppUser::where('email', $request->email)->first();
            if (! $user) {
                return $this->addErrorResponse(400, trans('global.User_not_found'), '');
            }

            if ($user) {
                $resultOtp = $this->validateOtpFromDB($user->phone, $user->phone_country, $request->reset_token);
                if ($resultOtp['status'] === 'success') {
                    return $this->successResponse(200, trans('global.RESET_OTP_Found_YOU_CAN_PROCEED'), [
                        'email' => $request->email,
                        'reset_token' => $request->reset_token,
                    ]);
                } else {
                    return $this->errorResponse(401, trans('global.RESET_OTP_ERROR'));
                }
            } else {
                return $this->errorResponse(404, trans('global.User_not_register'));
            }
        } catch (\Exception $e) {
            return $this->errorResponse(401, trans('global.something_wrong'));
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'reset_token' => ['required'],
                'password' => 'required',
                'confirm_password' => 'required|same:password',
            ]);

            if ($validator->fails()) {
                return $this->errorComputing($validator);
            }

            if (AppUser::where('email', $request->email)->exists()) {
                if (AppUser::where('email', $request->email)->where('reset_token', $request->reset_token)->exists()) {

                    AppUser::where('email', $request->email)->update(['password' => Hash::make($request->password)]);

                    return $this->successResponse(200, trans('global.Password_changed_successfully.'), [
                        'email' => $request->email,
                        'reset_token' => $request->reset_token,
                    ]);
                } else {
                    return $this->errorResponse(401, trans('global.RESET_OTP_ERROR'));
                }
            } else {
                return $this->errorResponse(404, trans('global.User_not_register'));
            }
        } catch (\Exception $e) {
            return $this->errorResponse(401, trans('global.something_wrong'));
        }
    }

    public function emailcheck(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->errorComputing($validator);
        }

        $email = strtolower((string) $request->email);
        $hasPending = PendingAppUserRegistration::where('email', $email)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', Carbon::now());
            })
            ->exists();

        if (AppUser::where('email', $email)->exists() || $hasPending) {

            return $this->successResponse(200, trans('global.email_already_exists'), [
                'email' => $email,
            ]);
        } else {

            return $this->errorResponse(401, trans('global.email_is_not_exists'));
        }
    }

    public function mobilecheck(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'numeric', 'digits_between:9,10'],
            'phone_country' => ['required'],
        ]);

        if ($validator->fails()) {
            return $this->errorComputing($validator);
        }

        $hasPending = PendingAppUserRegistration::where('phone', $request->phone)
            ->where('phone_country', $request->phone_country)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', Carbon::now());
            })
            ->exists();

        if (AppUser::where('phone', $request->phone)->where('phone_country', $request->phone_country)->exists() || $hasPending) {
            return $this->successResponse(200, trans('global.Phone_number_is_avilable'), ['phone' => $request->phone]);
        } else {

            return $this->errorResponse(401, trans('global.phone_number_not_exists.'));
        }
    }

    public function ResendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'numeric'],
            'phone_country' => ['required'],
            'user_type' => ['required'],
        ]);

        if ($validator->fails()) {
            $this->logMobileLoginEvent('Resend login OTP validation failed.', [
                'phone' => $request->phone,
                'phone_country' => $request->phone_country,
                'errors' => $validator->errors()->toArray(),
            ], 'warning');
            return $this->errorComputing($validator);
        }

        $this->logMobileLoginEvent('Resend login OTP request received.', [
            'phone' => $request->phone,
            'phone_country' => $request->phone_country,
        ]);
        $user = AppUser::where('phone', $request->phone)
            ->where('phone_country', $request->phone_country)
            ->where('user_type', $request->user_type)
            ->first();

        if (! $user) {
            $pending = PendingAppUserRegistration::where('phone', $request->phone)
                ->where('phone_country', $request->phone_country)
                ->where('user_type', $request->user_type)
                ->first();

            if (! $pending) {
                return $this->addErrorResponse(400, trans('global.User_not_found'), '');
            }

            if ($pending->expires_at && $pending->expires_at->lt(Carbon::now())) {
                $pending->delete();

                return $this->addErrorResponse(410, trans('global.OTPhas_expired'), '');
            }

            $otp = $this->generateOtp($pending->phone, $pending->phone_country);
            $this->logMobileLoginEvent('Resend OTP generated for pending signup.', [
                'phone' => $pending->phone,
                'phone_country' => $pending->phone_country,
                'otp' => $otp,
            ], 'warning');

            $otpSendResult = $this->sendPendingRegistrationOtpNotifications($pending, $otp);
            if (! $otpSendResult['ok']) {
                return $this->addErrorResponse(422, $otpSendResult['error'], '');
            }

            $pending->update([
                'otp_channel' => $otpSendResult['channel'],
                'otp_sent_at' => Carbon::now(),
                'expires_at' => Carbon::now()->addMinutes(10),
            ]);

            return $this->successResponse(200, trans('global.OTP_sent_successfully'), [
                'otp_value' => '',
                'phone' => $pending->phone,
                'phone_country' => $pending->phone_country,
                'email' => $pending->email,
                'otp_delivery_channel' => $otpSendResult['channel'],
                'otp_target_masked' => $otpSendResult['target_masked'],
                'otp_delivery_hint' => $otpSendResult['hint'],
                'status' => 'pending',
            ]);
        }

        $channels = $this->resolveAuthOtpChannels();
        if ($channels['email'] && ! $channels['mobile'] && empty($user->email)) {
            return $this->addErrorResponse(422, 'Email OTP is enabled, but no email is registered for this account.', '');
        }

        $otp = $this->generateOtp($user->phone, $user->phone_country);
        $this->logMobileLoginEvent('Resend login OTP generated.', [
            'phone' => $user->phone,
            'phone_country' => $user->phone_country,
            'user_id' => $user->id,
            'otp' => $otp,
        ]);

        $otpSendResult = $this->sendAuthOtpNotifications($user, $otp, 2);
        if (! $otpSendResult['ok']) {
            Log::warning('Resend login OTP delivery failed.', [
                'user_id' => $user->id,
                'message' => $otpSendResult['error'],
            ]);

            return $this->addErrorResponse(422, $otpSendResult['error'], '');
        }

        $otpUpdate = [
            'reset_token' => $otp,
        ];
        if (Schema::hasColumn('app_users', 'otp_expires_at')) {
            $otpUpdate['otp_expires_at'] = Carbon::now()->addMinutes(5);
        }
        $user->update($otpUpdate);

        $this->logMobileLoginEvent('Resend login OTP stored successfully.', [
            'phone' => $user->phone,
            'phone_country' => $user->phone_country,
            'user_id' => $user->id,
        ]);

        $responseData = [
            'otp_value' => '',
            'phone' => $user->phone,
            'phone_country' => $user->phone_country,
            'email' => $user->email,
            'otp_delivery_channel' => $otpSendResult['channel'],
            'otp_target_masked' => $otpSendResult['target_masked'],
            'otp_delivery_hint' => $otpSendResult['hint'],
        ];

        return $this->successResponse(200, trans('global.OTP_sent_successfully'), $responseData);
        try {
        } catch (\Exception $e) {
            return $this->errorResponse(401, trans('global.something_wrong'));
        }
    }

    public function ResendToken(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            return $this->errorComputing($validator);
        }
        $checkdata = AppUser::where('email', $request->email)->first();
        if ($checkdata) {
            $otp = $this->generateOtp($checkdata->phone, $checkdata->phone_country);
            $valuesArray = ['OTP' => $otp, 'first_name' => $checkdata->first_name, 'last_name' => $checkdata->last_name];
            $template_id = 37;
            $this->sendAllNotifications($valuesArray, $checkdata->id, $template_id);
            $update_otp = AppUser::where('email', $request->email)->update(['reset_token' => $otp]);
            $responseData = [];
            $responseData['reset_token'] = '';

            return $this->successResponse(200, trans('global.OTP_resent_succesfully'), $responseData);
        } else {
            return $this->errorResponse(409, trans('global.user_record_not_match'));
        }
        try {
        } catch (\Exception $e) {
            return $this->errorResponse(401, trans('global.something_wrong'));
        }
    }

    public function ResendTokenEmailChange(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'token' => ['required'],
        ]);

        if ($validator->fails()) {
            return $this->errorComputing($validator);
        }
        $checkdata = AppUser::where('token', $request->input('token'))->first();
        if ($checkdata) {
            $otp = $this->generateOtp($checkdata->phone, $checkdata->phone_country);

            $valuesArray = ['OTP' => $otp, 'first_name' => $checkdata->first_name, 'last_name' => $checkdata->last_name];
            if ($request->input('type') === 'email_reset') {
                $valuesArray['temp_email'] = $request->input('email');
            }
            $template_id = 37;
            $this->sendAllNotifications($valuesArray, $checkdata->id, $template_id);
            $update_otp = AppUser::where('email', $request->email)->update(['reset_token' => $otp]);
            $responseData = [];
            $responseData['reset_token'] = '';

            return $this->successResponse(200, trans('global.OTP_resent_succesfully'), $responseData);
        } else {
            return $this->errorResponse(409, trans('global.user_record_not_match'));
        }
        try {
        } catch (\Exception $e) {
            return $this->errorResponse(401, trans('global.something_wrong'));
        }
    }

    public function userValidate(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                'token' => ['required'],
            ]);

            if ($validator->fails()) {
                return $this->errorComputing($validator);
            }
            if (AppUser::where('token', $request->token)->exists()) {

                return $this->successResponse(200, trans('global.user_exist'));
            } else {
                return $this->errorResponse(401, trans('global.user_not_exist'));
            }
        } catch (\Exception $e) {
            return $this->errorResponse(401, trans('global.something_wrong'));
        }
    }

    public function updatePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => ['required'],
                'old_password' => ['required'],
                'new_password' => ['required'],
                'conf_new_password' => ['required', 'same:new_password'],
            ]);
            if ($request) {

                if ($validator->fails()) {
                    return $this->errorComputing($validator);
                }
            }

            $user = AppUser::where('token', $request->input('token'))->first();

            if (! $user) {
                return $this->addErrorResponse(419, trans('global.token_not_match'), '');
            }

            if (! Hash::check($request->input('old_password'), $user->password)) {
                return $this->addErrorResponse(500, trans('global.password_does_not_match'), '');
            }

            if (Hash::check($request->input('new_password'), $user->password)) {
                return $this->addErrorResponse(400, trans('global.new_password_same_as_old'), '');
            }
            $user->update([
                'password' => Hash::make($request->input('new_password')),
            ]);

            return $this->addSuccessResponse(200, trans('global.password_updated_successfully'), $user);
        } catch (\Exception $e) {
            return $this->addErrorResponse(500, trans('global.something_wrong'), '');
        }
    }

    public function getUserWallet(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'token' => ['required'],
        ]);

        if ($validator->fails()) {
            return $this->errorComputing($validator);
        }
        $user = AppUser::where('token', $request->input('token'))->first();
        if (! $user) {
            return $this->addErrorResponse(419, trans('global.token_not_match'), '');
        }

        try {
            $walletBalance = $this->getUserWalletBalance($user->id);

            return $this->addSuccessResponse(200, trans('global.Wallet_amount'), ['wallet_balance' => $walletBalance]);
        } catch (\Exception $e) {
            return $this->addErrorResponse(500, trans('global.user_not_found'), '');
        }
    }

    public function getUserWalletTransactions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => ['required'],
            'offset' => 'nullable|numeric|min:0',
            'limit' => 'nullable|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return $this->errorComputing($validator);
        }

        // Fetch pagination parameters
        $limit = $request->input('limit', 10);
        $offset = $request->input('offset', 0);

        try {
            $user = AppUser::where('token', $request->input('token'))->first();
            if (! $user) {
                return $this->addErrorResponse(419, trans('global.token_not_match'), '');
            }

            $WalletTransactionsDetails = Wallet::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get()
                ->toArray(); // Convert to array
            foreach ($WalletTransactionsDetails as &$transaction1) {
                $transaction1['created_at'] = Carbon::parse($transaction1['created_at'])->format('j M Y');
                $transaction1['updated_at'] = Carbon::parse($transaction1['updated_at'])->format('j M Y');
            }

            $WalletTransactionsDetails = collect($WalletTransactionsDetails);

            $nextOffset = $request->input('offset', 0) + count($WalletTransactionsDetails);
            if (empty($WalletTransactionsDetails)) {
                $nextOffset = -1;
            }

            return $this->addSuccessResponse(200, trans('global.Wallet_amount'), [
                'WalletTransactionsDetails' => $WalletTransactionsDetails,
                'offset' => $nextOffset,
            ]);
        } catch (\Exception $e) {
            return $this->addErrorResponse(500, trans('global.user_not_found'), '');
        }
    }

    public function getVendorWallet(Request $request)
    {
        // \DB::enableQueryLog();
        $validator = Validator::make($request->all(), [
            'token' => ['required'],
        ]);

        if ($validator->fails()) {
            return $this->errorComputing($validator);
        }
        $user = AppUser::where('token', $request->input('token'))->first();
        if (! $user) {
            return $this->addErrorResponse(419, trans('global.token_not_match'), '');
        }

        // try {

        $summary = $this->getVendorWalletSummary($user->id);
        $walletBalance = $summary['walletBalance'];
        $pendingToWithdrawl = $summary['pendingToWithdrawl'];
        $totalWithdrawled = $summary['totalWithdrawled'];
        $totalEarning = $summary['totalEarning'];
        $refunded = $summary['refunded'];
        $incoming_amount = $summary['incoming_amount'];
        $pendingPayout = $summary['pendingPayout'];

        // $queries = \DB::getQueryLog(); // Get all queries
        // dd(count($queries), $queries);

        return $this->addSuccessResponse(200, trans('global.vendor_Wallet_amount'), ['walletBalance' => $walletBalance, 'pendingToWithdrawl' => $pendingToWithdrawl, 'totalWithdrawled' => $totalWithdrawled, 'totalEarning' => $totalEarning, 'refunded' => $refunded, 'incoming_amount' => $incoming_amount, 'pendingPayout' => $pendingPayout]);
        try {
        } catch (\Exception $e) {
            return $this->addErrorResponse(500, trans('global.something_wrong'), '');
        }
    }

    public function getVendorWalletTransactions(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'token' => ['required'],
            'offset' => 'nullable|numeric|min:0',
        ]);
        $limit = $request->input('limit', 10);
        $offset = $request->input('offset', 0);
        if ($validator->fails()) {
            return $this->errorComputing($validator);
        }
        $user = AppUser::where('token', $request->input('token'))->first();
        if (! $user) {
            return $this->addErrorResponse(419, trans('global.token_not_match'), '');
        }

        try {
            $WalletTransactionsDetails = $this->getVendorWalletTransactionsDetails($user->id, $offset, $limit);

            return $this->addSuccessResponse(200, trans('global.vendor_Wallet_amount'), ['WalletTransactionsDetails' => $WalletTransactionsDetails['transactions'], 'offset' => $WalletTransactionsDetails['offset']]);
            // try {
        } catch (\Exception $e) {
            return $this->addErrorResponse(500, trans('global.something_wrong'), '');
        }
    }

    // fcmUpdate
    public function fcmUpdate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => ['required'],
                'player_id' => ['nullable', 'string'],
                'fcm' => ['nullable', 'string'],
                'device_id' => ['nullable', 'string'],
            ]);

            if ($validator->fails()) {
                return $this->errorComputing($validator);
            }

            $user = AppUser::where('token', $request->input('token'))->first();
            if (! $user) {
                return $this->addErrorResponse(404, trans('global.user_not_found'), '');
            }

            if ($request->filled('player_id')) {
                try {
                    $this->storeUserMeta($user->id, 'player_id', $request->input('player_id'));
                } catch (\Throwable $metaException) {
                    Log::warning('fcmUpdate player_id store failed; continuing with FCM/device update.', [
                        'user_id' => $user->id,
                        'player_id_present' => true,
                        'message' => $metaException->getMessage(),
                        'exception' => get_class($metaException),
                    ]);
                }
            }

            $updates = [];
            if (Schema::hasColumn($user->getTable(), 'fcm')) {
                $updates['fcm'] = $request->input('fcm');
            }
            if (Schema::hasColumn($user->getTable(), 'device_id')) {
                $updates['device_id'] = $request->input('device_id');
            }

            if (! empty($updates)) {
                $user->update($updates);
            }

            return $this->addSuccessResponse(200, trans('global.fcm_updated_successfully'), $user);
        } catch (\Throwable $e) {
            Log::error('fcmUpdate failed', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'token_present' => $request->filled('token'),
                'fcm_present' => $request->filled('fcm'),
                'device_id_present' => $request->filled('device_id'),
                'player_id_present' => $request->filled('player_id'),
            ]);

            return $this->addErrorResponse(500, trans('global.something_wrong'), '');
        }
    }

    public function deleteAccount(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => ['required'],
            ]);

            if ($validator->fails()) {
                return $this->errorComputing($validator);
            }

            // Find the user by token
            $user = AppUser::where('token', $request->token)->first();

            if (! $user) {
                return $this->errorResponse(404, trans('global.User_not_found'));
            }
            $token = Str::random(120);
            $user->token = $token;
            $user->save();

            // Delete the user
            $user->forceDelete();

            return $this->successResponse(200, trans('global.user_deleted_successfully'));
        } catch (\Exception $e) {
            return $this->errorResponse(401, trans('global.something_wrong'));
        }
    }

    public function insertPayout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => ['required'],
            'amount' => ['required', 'numeric'],
        ]);

        if ($validator->fails()) {
            return $this->errorComputing($validator);
        }

        $user = AppUser::where('token', $request->input('token'))->first();

        $payoutMethodId = $request->input('active_payout_method_id');

        $payoutMethodDetails = PayoutMethod::find($payoutMethodId);
        $payoutMethod = strtolower($payoutMethodDetails->name);

        if (! $user) {
            return $this->errorResponse(404, trans('global.User_not_found'));
        }

        try {
            $payoutStatus = 'Pending';
            $totalPayoutMoney = Payout::where('vendorid', $user->id)->where('payout_status', $payoutStatus)->sum('amount');
            $vendorWalletMoney = $this->getVendorWalletBalance($user->id);

            $withdrawalAmount = $request->input('amount');

            $withdrawalAmount = $withdrawalAmount + $totalPayoutMoney;

            if ($withdrawalAmount > $vendorWalletMoney) {
                return $this->errorResponse(404, trans('global.did_not_have_sufficient_balance'));
            } else {

                $payout = new Payout;
                $payout->vendorid = $user->id;
                $payout->amount = $request->input('amount');
                $payout->currency = $request->input('currency');
                if ($request->has('module_id')) {
                    $payout->module = $request->input('module_id');
                }
                // $payout->payment_method = '';
                $payout->payment_method = $payoutMethod;
                $payout->payout_status = 'Pending';
                $payout->save();

                $settings = GeneralSetting::whereIn('meta_key', ['general_email', 'general_default_currency'])
                    ->get()
                    ->keyBy('meta_key');

                $general_email = $settings['general_email'] ?? null;
                $general_default_currency = $settings['general_default_currency'] ?? null;

                $template_id = 4;
                $valuesArray = $user->toArray();
                $valuesArray = $user->only(['first_name', 'last_name', 'email', 'phone_country', 'phone']);
                $valuesArray['phone'] = $valuesArray['phone_country'].$valuesArray['phone'];
                $valuesArray['payout_amount'] = $request->input('amount');
                $valuesArray['payout_bank'] = $payout->payment_method;
                $valuesArray['support_email'] = $general_email->meta_value;
                $valuesArray['currency_code'] = $general_default_currency->meta_value;
                $valuesArray['payout_date'] = now()->format('Y-m-d');
                $this->sendAllNotifications($valuesArray, $user->id, $template_id);

                return $this->successResponse(200, trans('payout requested successfully'), ['payout' => $payout]);
            }
        } catch (\Exception $e) {
            return $this->errorResponse(500, trans('global.something_wrong').': '.$e->getMessage());
        }
    }

    public function getPayoutTransactions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => ['required'],
            'offset' => 'nullable|numeric|min:0',
            'limit' => 'nullable|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(400, trans('global.something_wrong'));
        }

        $limit = $request->input('limit', 10);
        $offset = $request->input('offset', 0);

        try {
            $user = AppUser::where('token', $request->input('token'))->first();

            if (! $user) {
                return $this->errorResponse(404, trans('global.User_not_found'));
            }

            $payoutTransactions = Payout::where('vendorid', $user->id)
                ->orderByDesc('created_at')
                ->offset($offset)
                ->take($limit)
                ->get()
                ->toArray(); // Convert to array

            foreach ($payoutTransactions as &$transaction) {
                $transaction['created_at'] = Carbon::parse($transaction['created_at'])->format('j M Y');
                $transaction['updated_at'] = Carbon::parse($transaction['updated_at'])->format('j M Y');
            }

            $payoutTransactions = collect($payoutTransactions);

            $nextOffset = $request->input('offset', 0) + count($payoutTransactions);

            if ($payoutTransactions->isEmpty()) {
                $nextOffset = -1;
            }

            return $this->successResponse(200, trans('global.Result_found'), [
                'payout_transactions' => $payoutTransactions,
                'offset' => $nextOffset,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(500, trans('global.something_wrong').': '.$e->getMessage());
        }
    }

    public function emailSmsNotification(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'type' => 'required',
            'value' => 'required',
        ]);

        $type = $request->type;
        $value = $request->value;

        if ($validator->fails()) {
            return $this->errorComputing($validator);
        }

        $user = AppUser::where('token', $request->input('token'))->first();
        if (! $user) {
            return $this->errorResponse(401, trans('global.user_not_found'));
        }

        if ($type == 'email') {
            $user->update(['email_notification' => $value]);

            return $this->successResponse(200, trans('global.emailNotification'), ['emailNotification' => $user]);
        }
        if ($type == 'push') {
            $user->update(['push_notification' => $value]);

            return $this->successResponse(200, trans('global.pushNotification'), ['emailsmsnotification' => $user]);
        }
        if ($type == 'sms') {
            $user->update(['sms_notification' => $value]);

            return $this->successResponse(200, trans('global.smsNotification'), ['emailsmsnotification' => $user]);
        }
    }

    public function puthostRequest(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'host_status' => 'required',
            'token' => 'required',
            'license_number' => 'required|string',
            'license_expire_date' => 'required|date',
            'driving_experience' => 'required|integer|min:0',
        ]);

        $host_status = $request->host_status;
        if ($validator->fails()) {
            return $this->errorComputing($validator);
        }
        $user = AppUser::where('token', $request->input('token'))->first();
        if (! $user) {
            return $this->errorResponse(401, trans('global.user_not_found'));
        }
        $hostFormData = $request->only([
            'host_status',
            'first_name',
            'last_name',
            'email',
            'phone',
            'country_code',
            'license_number',
            'license_expire_date',
            'driving_experience',
        ]);

        $userUpdated = $user->update(['host_status' => $host_status]);

        if ($userUpdated) {

            $imagePath = null;

            // if (!empty($request->input('identity_image'))) {
            //     $identityImage = $request->input('identity_image');
            //     $identityImageURL = $this->serveBase64Image($identityImage);
            //     $user->addMedia($identityImageURL)->toMediaCollection('identity_image');
            // }

            if (! empty($request->input('license_image'))) {
                $identityImage = $request->input('license_image');
                $identityImageURL = $this->serveBase64Image($identityImage);
                $user->addMedia($identityImageURL)->toMediaCollection('license_image');
            }

            AppUserMeta::updateOrCreate(
                ['user_id' => $user->id, 'meta_key' => 'host_form_data'],
                ['meta_value' => json_encode($hostFormData)]
            );

            // $template_id = 34;
            // $valuesArray = $user->toArray();
            // $valuesArray = $user->only(['first_name', 'last_name', 'email', 'phone_country', 'phone']);
            // $valuesArray['phone'] = $valuesArray['phone_country'] . $valuesArray['phone'];
            //  $settings = GeneralSetting::whereIn('meta_key', ['general_email'])->get()->keyBy('meta_key');

            // // Get the general email value safely
            // $general_email = $settings['general_email']->meta_value ?? null;

            // // Add support email to the values array
            // $valuesArray['support_email'] = $general_email;

            // $this->sendAllNotifications($valuesArray, $user->id, $template_id);
        }

        return $this->successResponse(200, trans('global.hostRequest'), ['host_status' => $host_status]);
    }

    public function gethostStatus(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->errorComputing($validator);
        }
        $user = AppUser::where('token', $request->input('token'))->first();
        if (! $user) {
            return $this->errorResponse(401, trans('global.user_not_found'));
        }

        return $this->successResponse(200, trans('global.hostRequest'), ['host_status' => $user->host_status]);
    }

    public function addEditVerificationDocuments(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|exists:app_users,token',
        ]);

        if ($validator->fails()) {
            return $this->errorComputing($validator);
        }

        $user_id = $this->checkUserByToken($request->token);
        if (! $user_id) {
            return $this->addErrorResponse(419, trans('global.token_not_match'), '');
        }

        try {
            $user = AppUser::find($user_id);
            $imageFields = [
                'driving_licence_front',
                'driving_licence_back',
                'aadhaar_front',
                'aadhaar_back',
                'pan_card',
                'vehicle_insurance_doc',
            ];
            $uploadedImages = [];
            $hasImageUploaded = false;

            foreach ($imageFields as $field) {
                if ($request->has($field) && ! empty($request->input($field))) {
                    if ($user->hasMedia($field)) {
                        $user->getFirstMedia($field)->delete();
                    }

                    $imageData = $request->input($field);
                    $imageUrl = $this->serveBase64Image($imageData);
                    $user->addMedia($imageUrl)->toMediaCollection($field);
                    $uploadedImages[$field] = $imageUrl;

                    // Save or update meta as "pending" status for each document
                    AppUserMeta::updateOrCreate(
                        ['user_id' => $user->id, 'meta_key' => $field.'_status'],
                        ['meta_value' => 'pending']
                    );
                    $hasImageUploaded = true;
                }
            }

            if (empty($uploadedImages)) {
                return $this->addErrorResponse(500, trans('global.No_image_found_in_the_request'), '');
            }
            if ($hasImageUploaded) {
                $user->host_status = '2';
                $user->save();
            }

            $userMeta = AppUserMeta::where('user_id', $user->id)->get();
            $user->meta_data = $userMeta;

            return $this->addSuccessResponse(200, trans('global.images_added_successfully'), $user);
        } catch (\Exception $e) {
            return $this->addErrorResponse(500, $e->getMessage(), $e->getMessage());
        }
    }

    public function getActiveRegions(Request $request)
    {
        $moduleId = (int) $request->input('module_id', 2);

        $regions = City::query()
            ->where('status', '1')
            ->where('module', $moduleId)
            ->orderBy('city_name')
            ->get()
            ->map(function (City $city) {
                return [
                    'id' => (string) $city->id,
                    'name' => $city->city_name,
                    'status' => 'active',
                ];
            })
            ->values();

        return $this->successResponse(200, 'Regions fetched successfully', $regions);
    }

    public function updateDriverPreferredRegion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|exists:app_users,token',
            'region_id' => ['required', 'integer', 'exists:cities,id'],
        ]);

        if ($validator->fails()) {
            return $this->errorComputing($validator);
        }

        $userId = $this->checkUserByToken($request->token);
        if (! $userId) {
            return $this->addErrorResponse(419, trans('global.token_not_match'), '');
        }

        $region = City::query()
            ->where('id', $request->input('region_id'))
            ->where('status', '1')
            ->first();

        if (! $region) {
            return $this->errorResponse(404, 'Preferred region not found');
        }

        AppUserMeta::updateOrCreate(
            [
                'user_id' => $userId,
                'meta_key' => 'preferred_region_id',
            ],
            [
                'meta_value' => (string) $region->id,
            ]
        );

        AppUserMeta::updateOrCreate(
            [
                'user_id' => $userId,
                'meta_key' => 'preferred_region_name',
            ],
            [
                'meta_value' => $region->city_name,
            ]
        );

        return $this->successResponse(200, 'Preferred location updated successfully', [
            'region_id' => (string) $region->id,
            'region_name' => $region->city_name,
        ]);
    }

    public function getRechargePlans(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|exists:app_users,token',
        ]);

        if ($validator->fails()) {
            return $this->errorComputing($validator);
        }

        $userId = $this->checkUserByToken($request->token);
        if (! $userId) {
            return $this->addErrorResponse(419, trans('global.token_not_match'), '');
        }

        $amountPerDay = (float) (GeneralSetting::getMetaValue('driver_recharge_amount_per_day') ?: 30);
        $currencyCode = strtoupper(GeneralSetting::getMetaValue('driver_recharge_currency') ?: 'INR');
        $gstPercentage = round((float) (GeneralSetting::getMetaValue('driver_recharge_gst_percentage') ?: 0), 2);

        $defaultPlans = [
            ['name' => 'Daily Plan', 'duration_days' => 1, 'amount' => $amountPerDay, 'sort_order' => 1],
            ['name' => 'Weekly Plan', 'duration_days' => 7, 'amount' => round($amountPerDay * 7, 2), 'sort_order' => 2],
            ['name' => 'Monthly Plan', 'duration_days' => 30, 'amount' => round($amountPerDay * 30, 2), 'sort_order' => 3],
        ];
        foreach ($defaultPlans as $plan) {
            $existing = DriverRechargePlan::where('duration_days', $plan['duration_days'])
                ->orderBy('id')
                ->first();

            if (! $existing) {
                DriverRechargePlan::create([
                    'name' => $plan['name'],
                    'duration_days' => $plan['duration_days'],
                    'amount' => $plan['amount'],
                    'currency_code' => $currencyCode,
                    'is_active' => 1,
                    'sort_order' => $plan['sort_order'],
                ]);
            }
        }

        $plans = DriverRechargePlan::active()
            ->orderBy('sort_order')
            ->orderBy('duration_days')
            ->get(['id', 'name', 'duration_days', 'amount', 'currency_code'])
            ->map(function ($plan) use ($gstPercentage) {
                $baseAmount = round((float) $plan->amount, 2);
                $gstAmount = round(($baseAmount * $gstPercentage) / 100, 2);
                $totalAmount = round($baseAmount + $gstAmount, 2);

                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'duration_days' => (int) $plan->duration_days,
                    'base_amount' => $baseAmount,
                    'gst_percentage' => $gstPercentage,
                    'gst_amount' => $gstAmount,
                    'amount' => $totalAmount,
                    'currency_code' => $plan->currency_code,
                ];
            })->values();

        return $this->addSuccessResponse(200, trans('global.Result_found'), [
            'amount_per_day' => $amountPerDay,
            'gst_percentage' => $gstPercentage,
            'currency_code' => $currencyCode,
            'plans' => $plans,
        ]);
    }

    public function getDriverRechargeStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|exists:app_users,token',
        ]);

        if ($validator->fails()) {
            return $this->errorComputing($validator);
        }

        $userId = $this->checkUserByToken($request->token);
        if (! $userId) {
            return $this->addErrorResponse(419, trans('global.token_not_match'), '');
        }

        $driver = AppUser::find($userId);
        if (! $driver) {
            return $this->addErrorResponse(404, trans('global.user_not_found'), '');
        }

        $amountPerDay = (float) (GeneralSetting::getMetaValue('driver_recharge_amount_per_day') ?: 30);
        $currencyCode = strtoupper(GeneralSetting::getMetaValue('driver_recharge_currency') ?: 'INR');
        $gstPercentage = round((float) (GeneralSetting::getMetaValue('driver_recharge_gst_percentage') ?: 0), 2);

        return $this->addSuccessResponse(200, trans('global.Result_found'), [
            'recharge_valid_until' => $driver->recharge_valid_until ? Carbon::parse($driver->recharge_valid_until)->toDateTimeString() : null,
            'recharge_active' => (bool) $driver->recharge_active,
            'can_ride' => $this->canDriverRide($driver),
            'amount_per_day' => $amountPerDay,
            'gst_percentage' => $gstPercentage,
            'currency_code' => $currencyCode,
        ]);
    }

    public function getRechargeInvoices(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|exists:app_users,token',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return $this->errorComputing($validator);
        }

        $driverId = $this->checkUserByToken($request->token);
        if (! $driverId) {
            return $this->addErrorResponse(419, trans('global.token_not_match'), '');
        }

        $billing = new RechargeBillingService();
        $invoices = DriverRechargeInvoice::where('driver_id', $driverId)
            ->latest('invoice_date')
            ->limit((int) $request->input('limit', 10))
            ->get()
            ->map(fn (DriverRechargeInvoice $invoice) => $billing->toPayload($invoice))
            ->values();

        return $this->addSuccessResponse(200, 'Recharge invoices retrieved successfully.', [
            'invoices' => $invoices,
        ]);
    }

    public function rechargeWallet(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|exists:app_users,token',
            'plan_id' => 'nullable|exists:driver_recharge_plans,id',
            'duration_days' => 'nullable|numeric|min:1|max:365',
            'currency' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return $this->errorComputing($validator);
        }

        $driverId = $this->checkUserByToken($request->token);
        if (! $driverId) {
            return $this->addErrorResponse(419, trans('global.token_not_match'), '');
        }

        $driver = AppUser::where('id', $driverId)->where('user_type', 'driver')->first();
        if (! $driver) {
            return $this->addErrorResponse(400, 'Only captains can recharge', '');
        }

        $amountPerDay = (float) (GeneralSetting::getMetaValue('driver_recharge_amount_per_day') ?: 30);
        $currencyCode = strtoupper(GeneralSetting::getMetaValue('driver_recharge_currency') ?: 'INR');
        $gstPercentage = round((float) (GeneralSetting::getMetaValue('driver_recharge_gst_percentage') ?: 0), 2);
        $durationDays = (int) $request->input('duration_days', 0);
        $baseAmount = 0.0;

        if ($request->filled('plan_id')) {
            $plan = DriverRechargePlan::active()->find($request->plan_id);
            if (! $plan) {
                return $this->addErrorResponse(404, 'Recharge plan not found', '');
            }
            $durationDays = (int) $plan->duration_days;
            $baseAmount = (float) $plan->amount;
            $currencyCode = strtoupper($plan->currency_code ?: $currencyCode);
        } else {
            if ($durationDays <= 0) {
                return $this->addErrorResponse(400, 'Duration is required when no plan is selected', '');
            }
            $baseAmount = round($durationDays * $amountPerDay, 2);
        }

        $gstAmount = round(($baseAmount * $gstPercentage) / 100, 2);
        $amount = round($baseAmount + $gstAmount, 2);

        if ($durationDays <= 0 || $baseAmount <= 0 || $amount <= 0) {
            return $this->addErrorResponse(400, 'Invalid recharge request', '');
        }

        $billing = new RechargeBillingService();

        try {
            $payload = DB::transaction(function () use ($driverId, $amount, $durationDays, $currencyCode, $baseAmount, $gstAmount, $gstPercentage, $request, $billing) {
                $driverLocked = AppUser::where('id', $driverId)
                    ->where('user_type', 'driver')
                    ->lockForUpdate()
                    ->firstOrFail();

                $walletSummary = DB::table('vendor_wallets')
                    ->where('vendor_id', $driverLocked->id)
                    ->selectRaw("
                        SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END) as total_credit,
                        SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END) as total_debit,
                        SUM(CASE WHEN type = 'refund' THEN amount ELSE 0 END) as total_refund
                    ")
                    ->lockForUpdate()
                    ->first();

                $walletBalance = (float) (($walletSummary->total_credit ?? 0) - (($walletSummary->total_debit ?? 0) + ($walletSummary->total_refund ?? 0)));
                if ($walletBalance < $amount) {
                    throw new \RuntimeException('Insufficient wallet balance for recharge');
                }

                $this->deductFromVendorWallet(
                    $driverLocked->id,
                    $amount,
                    null,
                    null,
                    "Driver recharge for {$durationDays} day(s) (incl GST)"
                );

                $base = $driverLocked->recharge_valid_until && Carbon::parse($driverLocked->recharge_valid_until)->gt(Carbon::now())
                    ? Carbon::parse($driverLocked->recharge_valid_until)
                    : Carbon::now();
                $driverLocked->recharge_valid_until = $base->copy()->addDays($durationDays);
                $driverLocked->recharge_active = true;
                $driverLocked->save();

                $invoice = $billing->createInvoice($driverLocked, [
                    'duration_days' => $durationDays,
                    'base_amount' => $baseAmount,
                    'gst_percentage' => $gstPercentage,
                    'gst_amount' => $gstAmount,
                    'amount' => $amount,
                    'currency_code' => $currencyCode,
                ], [
                    'plan_id' => $request->input('plan_id'),
                    'payment_method' => 'wallet',
                    'payment_status' => 'completed',
                    'metadata' => [
                        'source' => 'wallet_recharge',
                    ],
                ]);

                return [
                    'recharge_valid_until' => Carbon::parse($driverLocked->recharge_valid_until)->toDateTimeString(),
                    'recharge_active' => (bool) $driverLocked->recharge_active,
                    'can_ride' => true,
                    'duration_days' => $durationDays,
                    'base_amount' => round($baseAmount, 2),
                    'gst_percentage' => $gstPercentage,
                    'gst_amount' => round($gstAmount, 2),
                    'amount' => round($amount, 2),
                    'currency_code' => $currencyCode,
                    'invoice' => $billing->toPayload($invoice),
                ];
            });

            return $this->addSuccessResponse(200, 'Recharge successful', $payload);
        } catch (\Throwable $e) {
            return $this->addErrorResponse(400, $e->getMessage(), '');
        }
    }

    /**
     * Start an online recharge payment using Razorpay. Returns order payload for client checkout.
     */
    public function startRechargePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|exists:app_users,token',
            'plan_id' => 'nullable|exists:driver_recharge_plans,id',
            'duration_days' => 'nullable|numeric|min:1|max:365',
            'amount' => 'nullable|numeric|min:1',
            'currency' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return $this->errorComputing($validator);
        }

        $driverId = $this->checkUserByToken($request->token);
        if (! $driverId) {
            return $this->addErrorResponse(419, trans('global.token_not_match'), '');
        }

        $driver = AppUser::where('id', $driverId)->where('user_type', 'driver')->first();
        if (! $driver) {
            return $this->addErrorResponse(400, 'Only captains can recharge', '');
        }

        // Re-use pricing logic from rechargeWallet
        $amountPerDay = (float) (GeneralSetting::getMetaValue('driver_recharge_amount_per_day') ?: 30);
        $currencyCode = strtoupper($request->input('currency', GeneralSetting::getMetaValue('driver_recharge_currency') ?: 'INR'));
        $gstPercentage = round((float) (GeneralSetting::getMetaValue('driver_recharge_gst_percentage') ?: 0), 2);
        $durationDays = (int) $request->input('duration_days', 0);
        $baseAmount = (float) $request->input('amount', 0);

        if ($request->filled('plan_id')) {
            $plan = DriverRechargePlan::active()->find($request->plan_id);
            if (! $plan) {
                return $this->addErrorResponse(404, 'Recharge plan not found', '');
            }
            $durationDays = (int) $plan->duration_days;
            $baseAmount = (float) $plan->amount;
            $currencyCode = strtoupper($plan->currency_code ?: $currencyCode);
        } else {
            if ($durationDays <= 0 && $baseAmount > 0) {
                $durationDays = (int) ceil($baseAmount / max($amountPerDay, 1));
            }
            if ($baseAmount <= 0 && $durationDays > 0) {
                $baseAmount = round($durationDays * $amountPerDay, 2);
            }
        }

        $gstAmount = round(($baseAmount * $gstPercentage) / 100, 2);
        $amount = round($baseAmount + $gstAmount, 2);

        if ($durationDays <= 0 || $baseAmount <= 0 || $amount <= 0) {
            return $this->addErrorResponse(400, 'Invalid recharge request', '');
        }

        if (! $this->isRazorpayConfiguredForEnvironment()) {
            return $this->addErrorResponse(503, 'Online recharge is not configured yet. Please try wallet recharge or contact support.', [
                'missing' => $this->razorpayMissingFields(),
            ]);
        }

        // Create Razorpay order
        try {
            $strategy = new \App\Strategies\RazorpayStrategy();
            $orderPayload = $strategy->createOrder($amount, $currencyCode, 'recharge_'.$driverId, [
                'driver_id' => $driverId,
                'plan_id' => $request->input('plan_id'),
                'duration_days' => $durationDays,
            ]);
        } catch (\Throwable $e) {
            return $this->addErrorResponse(503, 'Online recharge service is temporarily unavailable.', '');
        }

        if ($orderPayload['status'] !== 'success') {
            return $this->addErrorResponse(400, $orderPayload['message'] ?? 'Unable to create order', '');
        }

        $data = [
            'order' => $orderPayload['data'],
            'base_amount' => round($baseAmount, 2),
            'gst_percentage' => $gstPercentage,
            'gst_amount' => round($gstAmount, 2),
            'amount' => $amount,
            'currency' => $currencyCode,
            'driver_id' => $driverId,
            'plan_id' => $request->input('plan_id'),
            'duration_days' => $durationDays,
            'razorpay_key' => $orderPayload['key_id'] ?? null,
        ];

        return $this->addSuccessResponse(200, 'Order created', $data);
    }

    /**
     * Confirm Razorpay recharge payment and activate recharge.
     */
    public function confirmRechargePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|exists:app_users,token',
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
            'plan_id' => 'nullable|exists:driver_recharge_plans,id',
            'duration_days' => 'nullable|numeric|min:1|max:365',
        ]);

        if ($validator->fails()) {
            return $this->errorComputing($validator);
        }

        $driverId = $this->checkUserByToken($request->token);
        if (! $driverId) {
            return $this->addErrorResponse(419, trans('global.token_not_match'), '');
        }

        if (! $this->isRazorpayConfiguredForEnvironment()) {
            return $this->addErrorResponse(503, 'Online recharge is not configured yet. Please contact support.', [
                'missing' => $this->razorpayMissingFields(),
            ]);
        }

        $strategy = new \App\Strategies\RazorpayStrategy();
        $verified = $strategy->verifySignature(
            $request->razorpay_order_id,
            $request->razorpay_payment_id,
            $request->razorpay_signature
        );

        if (! $verified) {
            return $this->addErrorResponse(400, 'Signature verification failed', '');
        }

        $currencyCode = strtoupper(GeneralSetting::getMetaValue('driver_recharge_currency') ?: 'INR');
        $amountPerDay = (float) (GeneralSetting::getMetaValue('driver_recharge_amount_per_day') ?: 30);
        $gstPercentage = round((float) (GeneralSetting::getMetaValue('driver_recharge_gst_percentage') ?: 0), 2);
        $durationDays = (int) $request->input('duration_days', 0);
        $baseAmount = 0.0;

        if ($request->filled('plan_id')) {
            $plan = DriverRechargePlan::active()->find($request->plan_id);
            if (! $plan) {
                return $this->addErrorResponse(404, 'Recharge plan not found', '');
            }
            $durationDays = (int) $plan->duration_days;
            $baseAmount = (float) $plan->amount;
            $currencyCode = strtoupper($plan->currency_code ?: $currencyCode);
        } else {
            if ($durationDays <= 0) {
                return $this->addErrorResponse(400, 'Duration is required when no plan is selected', '');
            }
            $baseAmount = round($durationDays * $amountPerDay, 2);
        }

        $gstAmount = round(($baseAmount * $gstPercentage) / 100, 2);
        $amount = round($baseAmount + $gstAmount, 2);

        if ($durationDays <= 0 || $baseAmount <= 0 || $amount <= 0) {
            return $this->addErrorResponse(400, 'Invalid recharge request', '');
        }

        $billing = new RechargeBillingService();

        $payload = DB::transaction(function () use ($driverId, $durationDays, $amount, $currencyCode, $request, $baseAmount, $gstAmount, $gstPercentage, $billing) {
            $driverLocked = AppUser::where('id', $driverId)
                ->where('user_type', 'driver')
                ->lockForUpdate()
                ->firstOrFail();

            $base = $driverLocked->recharge_valid_until && Carbon::parse($driverLocked->recharge_valid_until)->gt(Carbon::now())
                ? Carbon::parse($driverLocked->recharge_valid_until)
                : Carbon::now();
            $driverLocked->recharge_valid_until = $base->copy()->addDays($durationDays);
            $driverLocked->recharge_active = true;
            $driverLocked->save();

            // Optionally record transaction
            DB::table('vendor_wallets')->insert([
                'vendor_id' => $driverLocked->id,
                'amount' => $amount,
                'type' => 'credit',
                'payment_method' => 'razorpay',
                'payment_status' => 'completed',
                'txn_id' => $request->razorpay_payment_id,
                'currency' => $currencyCode,
                'note' => 'Driver recharge online payment',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $invoice = $billing->createInvoice($driverLocked, [
                'duration_days' => $durationDays,
                'base_amount' => $baseAmount,
                'gst_percentage' => $gstPercentage,
                'gst_amount' => $gstAmount,
                'amount' => $amount,
                'currency_code' => $currencyCode,
            ], [
                'plan_id' => $request->input('plan_id'),
                'payment_method' => 'razorpay',
                'payment_status' => 'completed',
                'transaction_id' => $request->razorpay_payment_id,
                'metadata' => [
                    'source' => 'confirm_recharge_payment',
                    'razorpay_order_id' => $request->razorpay_order_id,
                ],
            ]);

            return [
                'recharge_valid_until' => Carbon::parse($driverLocked->recharge_valid_until)->toDateTimeString(),
                'recharge_active' => (bool) $driverLocked->recharge_active,
                'can_ride' => true,
                'duration_days' => $durationDays,
                'base_amount' => round($baseAmount, 2),
                'gst_percentage' => $gstPercentage,
                'gst_amount' => round($gstAmount, 2),
                'amount' => round($amount, 2),
                'currency_code' => $currencyCode,
                'invoice' => $billing->toPayload($invoice),
            ];
        });

        return $this->addSuccessResponse(200, 'Recharge activated', $payload);
    }

    public function getSupportTickets(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|exists:app_users,token',
        ]);
        if ($validator->fails()) {
            return $this->errorComputing($validator);
        }

        $userId = $this->checkUserByToken($request->token);
        if (! $userId) {
            return $this->addErrorResponse(419, trans('global.token_not_match'), '');
        }

        $moduleId = $this->getModuleIdOrDefault($request);
        $tickets = SupportTicket::where('user_id', $userId)
            ->where('module', $moduleId)
            ->orderByDesc('id')
            ->get()
            ->map(function (SupportTicket $ticket) {
                $lastReply = SupportTicketReply::where('thread_id', $ticket->id)
                    ->orderByDesc('id')
                    ->first();

                return [
                    'id' => $ticket->id,
                    'thread_id' => $ticket->thread_id ?: $ticket->id,
                    'title' => $ticket->title,
                    'description' => $ticket->description,
                    'thread_status' => (int) $ticket->thread_status,
                    'last_message' => $lastReply?->message ?: $ticket->description,
                    'updated_at' => optional($ticket->updated_at)->toDateTimeString(),
                ];
            })->values();

        return $this->addSuccessResponse(200, 'Success', $tickets);
    }

    public function createSupportTicket(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|exists:app_users,token',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
        ]);
        if ($validator->fails()) {
            return $this->errorComputing($validator);
        }

        $userId = $this->checkUserByToken($request->token);
        if (! $userId) {
            return $this->addErrorResponse(419, trans('global.token_not_match'), '');
        }

        $moduleId = $this->getModuleIdOrDefault($request);
        $ticket = SupportTicket::create([
            'user_id' => $userId,
            'title' => trim((string) $request->title),
            'description' => trim((string) $request->description),
            'thread_status' => 1,
            'module' => $moduleId,
        ]);

        // Keep thread_id consistent for legacy UI references.
        $ticket->thread_id = (string) $ticket->id;
        $ticket->save();

        SupportTicketReply::create([
            'thread_id' => $ticket->id,
            'user_id' => $userId,
            'is_admin_reply' => 0,
            'message' => trim((string) $request->description),
            'reply_status' => 1,
        ]);

        return $this->addSuccessResponse(200, 'Ticket created successfully', [
            'id' => $ticket->id,
            'thread_id' => $ticket->thread_id,
            'thread_status' => (int) $ticket->thread_status,
        ]);
    }

    public function getSupportTicketThread(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|exists:app_users,token',
            'ticket_id' => 'required|integer|exists:support_tickets,id',
        ]);
        if ($validator->fails()) {
            return $this->errorComputing($validator);
        }

        $userId = $this->checkUserByToken($request->token);
        if (! $userId) {
            return $this->addErrorResponse(419, trans('global.token_not_match'), '');
        }

        $ticket = SupportTicket::where('id', $request->ticket_id)
            ->where('user_id', $userId)
            ->first();
        if (! $ticket) {
            return $this->addErrorResponse(404, 'Ticket not found', '');
        }

        $replies = SupportTicketReply::where('thread_id', $ticket->id)
            ->orderBy('id')
            ->get()
            ->map(fn (SupportTicketReply $reply) => [
                'id' => $reply->id,
                'message' => $reply->message,
                'is_admin_reply' => (bool) $reply->is_admin_reply,
                'created_at' => optional($reply->created_at)->toDateTimeString(),
            ])->values();

        return $this->addSuccessResponse(200, 'Success', [
            'id' => $ticket->id,
            'thread_id' => $ticket->thread_id ?: $ticket->id,
            'title' => $ticket->title,
            'thread_status' => (int) $ticket->thread_status,
            'replies' => $replies,
        ]);
    }

    public function replySupportTicket(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|exists:app_users,token',
            'ticket_id' => 'required|integer|exists:support_tickets,id',
            'message' => 'required|string|max:5000',
        ]);
        if ($validator->fails()) {
            return $this->errorComputing($validator);
        }

        $userId = $this->checkUserByToken($request->token);
        if (! $userId) {
            return $this->addErrorResponse(419, trans('global.token_not_match'), '');
        }

        $ticket = SupportTicket::where('id', $request->ticket_id)
            ->where('user_id', $userId)
            ->first();
        if (! $ticket) {
            return $this->addErrorResponse(404, 'Ticket not found', '');
        }
        if ((int) $ticket->thread_status !== 1) {
            return $this->addErrorResponse(400, 'Ticket is closed. Reopen it before replying.', '');
        }

        $reply = SupportTicketReply::create([
            'thread_id' => $ticket->id,
            'user_id' => $userId,
            'is_admin_reply' => 0,
            'message' => trim((string) $request->message),
            'reply_status' => 1,
        ]);

        $ticket->touch();

        return $this->addSuccessResponse(200, 'Reply sent successfully', [
            'reply_id' => $reply->id,
        ]);
    }

    public function updateSupportTicketStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|exists:app_users,token',
            'ticket_id' => 'required|integer|exists:support_tickets,id',
            'thread_status' => 'required|in:0,1',
        ]);
        if ($validator->fails()) {
            return $this->errorComputing($validator);
        }

        $userId = $this->checkUserByToken($request->token);
        if (! $userId) {
            return $this->addErrorResponse(419, trans('global.token_not_match'), '');
        }

        $ticket = SupportTicket::where('id', $request->ticket_id)
            ->where('user_id', $userId)
            ->first();
        if (! $ticket) {
            return $this->addErrorResponse(404, 'Ticket not found', '');
        }

        $ticket->thread_status = (int) $request->thread_status;
        $ticket->save();

        return $this->addSuccessResponse(200, 'Ticket status updated', [
            'ticket_id' => $ticket->id,
            'thread_status' => (int) $ticket->thread_status,
        ]);
    }

    public function getMaskedCallNumber(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'driver_id' => 'required',
            'token' => 'required|exists:app_users,token',
        ]);

        if ($validator->fails()) {
            return $this->errorComputing($validator);
        }

        $userId = $this->checkUserByToken($request->token);
        if (! $userId) {
            return $this->addErrorResponse(419, trans('global.token_not_match'), '');
        }

        $driver = AppUser::where('id', $request->driver_id)->orWhere('firestore_id', $request->driver_id)->first();
        if (! $driver) {
            return $this->addErrorResponse(404, trans('global.driver_not_found'), '');
        }

        $proxy = $this->getProxyContactNumber();
        $phone = $proxy !== ''
            ? $proxy
            : ($driver->phone_country && $driver->phone ? $driver->phone_country.$driver->phone : (string) $driver->phone);

        if (trim((string) $phone) === '') {
            return $this->addErrorResponse(503, 'Call feature is not configured yet. Please contact support.', '');
        }

        return $this->addSuccessResponse(200, 'Success', ['phone_number' => $phone]);
    }

    public function sendRideChatPush(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ride_id' => 'required|string|max:191',
            'message' => 'nullable|string|max:400',
            'message_type' => 'nullable|string|in:text,image',
            'token' => 'required|exists:app_users,token',
        ]);

        if ($validator->fails()) {
            return $this->errorComputing($validator);
        }

        $senderId = $this->checkUserByToken($request->token);
        if (! $senderId) {
            return $this->addErrorResponse(419, trans('global.token_not_match'), '');
        }

        $rideId = trim((string) $request->ride_id);
        $extension = BookingExtension::with('booking')
            ->where('ride_id', $rideId)
            ->orderByDesc('id')
            ->first();

        if (! $extension || ! $extension->booking) {
            return $this->addErrorResponse(404, 'Ride not found for notification.', '');
        }

        $booking = $extension->booking;
        $riderId = (string) $booking->userid;
        $driverId = (string) $booking->host_id;
        $senderIdStr = (string) $senderId;

        if ($senderIdStr !== $riderId && $senderIdStr !== $driverId) {
            return $this->addErrorResponse(403, 'You are not allowed to send notification for this ride.', '');
        }

        $receiverId = $senderIdStr === $riderId ? $driverId : $riderId;
        $receiver = AppUser::with('metadata')->find($receiverId);
        if (! $receiver) {
            return $this->addErrorResponse(404, 'Receiver not found.', '');
        }

        if ((string) ($receiver->push_notification ?? '1') === '0') {
            return $this->addSuccessResponse(200, 'Push disabled for receiver.', ['sent' => false, 'reason' => 'push_disabled']);
        }

        $playerId = optional($receiver->metadata->firstWhere('meta_key', 'player_id'))->meta_value;
        $deviceToken = $receiver->fcm ?: $playerId;
        if (empty($deviceToken)) {
            return $this->addSuccessResponse(200, 'Receiver has no notification token.', ['sent' => false, 'reason' => 'missing_token']);
        }

        $sender = AppUser::find($senderId);
        $senderName = trim(($sender->first_name ?? '').' '.($sender->last_name ?? ''));
        $senderName = $senderName !== '' ? $senderName : 'Someone';

        $messageType = $request->input('message_type', 'text');
        $messageText = trim((string) $request->input('message', ''));
        if ($messageType === 'image' || $messageText === '') {
            $messageText = 'Sent an image';
        }

        $subject = $senderName.' sent a message';
        $notificationUserType = (string) $receiver->id === $driverId ? 'driver' : null;
        $payload = [
            'route' => 'ride_chat',
            'ride_id' => $rideId,
            'sender_id' => $senderIdStr,
            'receiver_id' => (string) $receiver->id,
            'message_type' => $messageType,
        ];

        try {
            $sent = $this->sendFcmMessage($deviceToken, $subject, $messageText, $payload, 0, $notificationUserType);
        } catch (\Throwable $e) {
            \Log::warning('ride_chat_push_failed', [
                'ride_id' => $rideId,
                'sender_id' => $senderIdStr,
                'receiver_id' => (string) $receiver->id,
                'error' => $e->getMessage(),
            ]);

            return $this->addErrorResponse(500, 'Failed to send push notification.', '');
        }

        if (! $sent) {
            \Log::warning('ride_chat_push_failed', [
                'ride_id' => $rideId,
                'sender_id' => $senderIdStr,
                'receiver_id' => (string) $receiver->id,
                'error' => 'FCM send returned false.',
            ]);

            return $this->addErrorResponse(500, 'Failed to send push notification.', '');
        }

        return $this->addSuccessResponse(200, 'Push notification sent.', ['sent' => true]);
    }

    public function getDriverForHire(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'nullable|string',
            'driver_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorComputing($validator);
        }

        $riderId = $this->resolveApiUserId($request);
        if (! $riderId) {
            return $this->addErrorResponse(419, trans('global.token_not_match'), '');
        }

        $driver = AppUser::where(function ($query) use ($request) {
            $query->where('id', $request->driver_id)
                ->orWhere('firestore_id', $request->driver_id);
        })
            ->where('user_type', 'driver')
            ->where('status', 1)
            ->first();

        if (! $driver) {
            return $this->addErrorResponse(404, trans('global.user_not_found'), '');
        }

        if ((string) $driver->host_status !== '1') {
            return $this->addErrorResponse(403, 'Captain not approved yet', '');
        }

        if (! $this->canDriverRide($driver)) {
            return response()->json([
                'status' => 403,
                'message' => 'Captain recharge required',
                'data' => ['error_key' => 'recharge_required'],
                'error' => 'Captain recharge required',
            ], 403);
        }

        $item = Item::where('userid_id', $driver->id)->first();
        $itemVehicle = $item ? ItemVehicle::where('item_id', $item->id)->first() : null;
        $pricingConfig = $this->getHireDurationPricingConfig();

        $data = [
            'driver_id' => $driver->id,
            'first_name' => $driver->first_name,
            'last_name' => $driver->last_name,
            'driver_name' => trim(($driver->first_name ?? '').' '.($driver->last_name ?? '')),
            'vehicle_number' => $itemVehicle->vehicle_registration_number ?? $item->registration_number ?? null,
            'vehicle_registration_number' => $itemVehicle->vehicle_registration_number ?? $item->registration_number ?? null,
            'vehicle_make' => $item->make ?? null,
            'vehicle_model' => $item->model ?? null,
            'vehicle_color' => $itemVehicle->color ?? null,
            'can_ride' => true,
            'hire_currency_code' => $pricingConfig['currency_code'],
            'hire_rate_per_hour' => $pricingConfig['rate_per_hour'],
            'duration_pricing_options' => $pricingConfig['duration_options'],
            'custom_duration_min_hours' => $pricingConfig['custom_min_hours'],
            'custom_duration_max_hours' => $pricingConfig['custom_max_hours'],
        ];

        return $this->addSuccessResponse(200, trans('global.Result_found'), $data);
    }

    public function bookHireByQr(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'nullable|string',
            'driver_id' => 'required|string',
            'duration_hours' => 'nullable|numeric|min:1|max:720',
            'duration_key' => 'nullable|string|max:30',
            'payment_method' => 'nullable|string|max:30',
            'currency_code' => 'nullable|string|max:10',
            'amount_to_pay' => 'nullable|numeric|min:0',
            'client_request_id' => 'nullable|string|max:64',
        ]);

        if ($validator->fails()) {
            return $this->errorComputing($validator);
        }

        $userId = $this->resolveApiUserId($request);
        if (! $userId) {
            return $this->addErrorResponse(419, trans('global.token_not_match'), '');
        }

        $rider = AppUser::find($userId);
        $driver = AppUser::where(function ($query) use ($request) {
            $query->where('id', $request->driver_id)
                ->orWhere('firestore_id', $request->driver_id);
        })
            ->where('user_type', 'driver')
            ->where('status', 1)
            ->first();

        if (! $driver) {
            return $this->addErrorResponse(404, trans('global.user_not_found'), '');
        }

        if ($rider && $rider->id === $driver->id) {
            return $this->addErrorResponse(400, 'You cannot hire your own vehicle', '');
        }

        if ((string) $driver->host_status !== '1') {
            return $this->addErrorResponse(403, 'Captain not approved yet', '');
        }

        if (! $this->canDriverRide($driver)) {
            return response()->json([
                'status' => 403,
                'message' => 'Captain recharge required',
                'data' => ['error_key' => 'recharge_required'],
                'error' => 'Captain recharge required',
            ], 403);
        }

        $clientRequestId = trim((string) $request->input('client_request_id', ''));
        if ($clientRequestId !== '') {
            $existingHire = HireBooking::where('user_id', $userId)
                ->where('client_request_id', $clientRequestId)
                ->first();
            if ($existingHire) {
                return $this->addSuccessResponse(200, 'Hire booked successfully', [
                    'hire_booking_id' => $existingHire->id,
                    'status' => $existingHire->status,
                    'duration_hours' => $existingHire->duration_hours,
                    'start_at' => $existingHire->start_at,
                    'end_at' => $existingHire->end_at,
                    'amount_to_pay' => $existingHire->amount_to_pay,
                    'currency_code' => $existingHire->currency_code,
                    'idempotent_replay' => true,
                ]);
            }
        }

        $item = Item::where('userid_id', $driver->id)->first();
        $itemVehicle = $item ? ItemVehicle::where('item_id', $item->id)->first() : null;

        $pricingConfig = $this->getHireDurationPricingConfig();
        $resolvedDuration = $this->resolveHireDurationAndAmount($request, $pricingConfig);
        if (! $resolvedDuration) {
            return $this->addErrorResponse(400, 'Invalid hire duration selection', '');
        }

        $durationHours = $resolvedDuration['duration_hours'];
        $amountToPay = $resolvedDuration['amount_to_pay'];
        $currencyCode = $pricingConfig['currency_code'];

        $startAt = Carbon::now();
        $endAt = (clone $startAt)->addHours($durationHours);
        $overlap = $this->getOverlappingHireBooking($driver->id, $startAt, $endAt);
        if ($overlap) {
            return response()->json([
                'status' => 409,
                'ResponseCode' => 409,
                'message' => 'Driver already has an active hire booking in this time window',
                'next_available_at' => optional($overlap->end_at)->toDateTimeString(),
                'data' => ['next_available_at' => optional($overlap->end_at)->toDateTimeString()],
                'error' => 'Driver already has an active hire booking in this time window',
            ], 409);
        }

        try {
            $hireBooking = HireBooking::create([
                'client_request_id' => $clientRequestId !== '' ? $clientRequestId : null,
                'user_id' => $userId,
                'driver_id' => $driver->id,
                'item_id' => $item ? $item->id : null,
                'duration_hours' => $durationHours,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'amount_to_pay' => $amountToPay,
                'currency_code' => $currencyCode,
                'payment_method' => $request->input('payment_method', 'cash'),
                'payment_status' => 'pending',
                'status' => 'booked',
            ]);
        } catch (\Throwable $e) {
            if ($clientRequestId !== '') {
                $existingHire = HireBooking::where('user_id', $userId)
                    ->where('client_request_id', $clientRequestId)
                    ->first();
                if ($existingHire) {
                    return $this->addSuccessResponse(200, 'Hire booked successfully', [
                        'hire_booking_id' => $existingHire->id,
                        'status' => $existingHire->status,
                        'duration_hours' => $existingHire->duration_hours,
                        'start_at' => $existingHire->start_at,
                        'end_at' => $existingHire->end_at,
                        'amount_to_pay' => $existingHire->amount_to_pay,
                        'currency_code' => $existingHire->currency_code,
                        'idempotent_replay' => true,
                    ]);
                }
            }
            throw $e;
        }

        $data = [
            'hire_booking_id' => $hireBooking->id,
            'status' => $hireBooking->status,
            'duration_hours' => $hireBooking->duration_hours,
            'start_at' => $hireBooking->start_at,
            'end_at' => $hireBooking->end_at,
            'amount_to_pay' => $hireBooking->amount_to_pay,
            'currency_code' => $hireBooking->currency_code,
            'applied_duration_key' => $resolvedDuration['duration_key'],
            'fare_breakdown' => [
                'rate_per_hour' => $pricingConfig['rate_per_hour'],
                'duration_hours' => $durationHours,
                'amount_to_pay' => $amountToPay,
                'currency_code' => $currencyCode,
            ],
            'driver_name' => trim(($driver->first_name ?? '').' '.($driver->last_name ?? '')),
            'vehicle_number' => $itemVehicle->vehicle_registration_number ?? $item->registration_number ?? null,
            'vehicle_make' => $item->make ?? null,
            'vehicle_model' => $item->model ?? null,
            'vehicle_color' => $itemVehicle->color ?? null,
        ];

        return $this->addSuccessResponse(200, 'Hire booked successfully', $data);
    }

    private function resolveApiUserId(Request $request): ?int
    {
        $authUser = Auth::guard('sanctum')->user();
        if ($authUser instanceof AppUser && $authUser->id) {
            return (int) $authUser->id;
        }

        $legacyToken = trim((string) $request->input('token', ''));
        if ($legacyToken === '') {
            return null;
        }

        return $this->checkUserByToken($legacyToken);
    }

    private function getHireDurationPricingConfig(): array
    {
        $ratePerHour = (float) (GeneralSetting::getMetaValue('hire_rate_per_hour') ?: 100);
        $currencyCode = strtoupper(
            GeneralSetting::getMetaValue('hire_currency')
            ?: GeneralSetting::getMetaValue('driver_recharge_currency')
            ?: 'INR'
        );
        $customMinHours = max(1, (int) (GeneralSetting::getMetaValue('hire_custom_min_hours') ?: 1));
        $customMaxHours = max($customMinHours, (int) (GeneralSetting::getMetaValue('hire_custom_max_hours') ?: 720));

        $durationOptions = [
            ['key' => '4h', 'label' => '4 hours', 'hours' => 4, 'multiplier' => 1],
            ['key' => '6h', 'label' => '6 hours', 'hours' => 6, 'multiplier' => 0.95],
            ['key' => '1d', 'label' => '1 day', 'hours' => 24, 'multiplier' => 0.9],
        ];

        $configured = GeneralSetting::getMetaValue('hire_duration_options_json');
        if (is_string($configured) && $configured !== '') {
            $decoded = json_decode($configured, true);
            if (is_array($decoded) && ! empty($decoded)) {
                $candidateOptions = [];
                foreach ($decoded as $opt) {
                    if (! is_array($opt)) {
                        continue;
                    }
                    $hours = (int) ($opt['hours'] ?? 0);
                    if ($hours <= 0) {
                        continue;
                    }
                    $key = (string) ($opt['key'] ?? ($hours.'h'));
                    $label = (string) ($opt['label'] ?? ($hours.' hours'));
                    $fixedAmount = isset($opt['amount']) ? (float) $opt['amount'] : null;
                    $multiplier = isset($opt['multiplier']) ? (float) $opt['multiplier'] : 1.0;
                    $candidateOptions[] = [
                        'key' => $key,
                        'label' => $label,
                        'hours' => $hours,
                        'amount' => $fixedAmount && $fixedAmount > 0 ? $fixedAmount : round($hours * $ratePerHour * max($multiplier, 0.01), 2),
                    ];
                }
                if (! empty($candidateOptions)) {
                    $durationOptions = $candidateOptions;
                }
            }
        }

        $durationOptions = array_map(function (array $opt) use ($ratePerHour) {
            $hours = max(1, (int) ($opt['hours'] ?? 1));
            $amount = isset($opt['amount']) ? (float) $opt['amount'] : round($hours * $ratePerHour * (float) ($opt['multiplier'] ?? 1), 2);

            return [
                'key' => (string) ($opt['key'] ?? ($hours.'h')),
                'label' => (string) ($opt['label'] ?? ($hours.' hours')),
                'hours' => $hours,
                'amount' => round(max($amount, 1), 2),
            ];
        }, $durationOptions);

        usort($durationOptions, fn (array $a, array $b) => $a['hours'] <=> $b['hours']);

        return [
            'rate_per_hour' => $ratePerHour,
            'currency_code' => $currencyCode,
            'custom_min_hours' => $customMinHours,
            'custom_max_hours' => $customMaxHours,
            'duration_options' => $durationOptions,
        ];
    }

    private function resolveHireDurationAndAmount(Request $request, array $pricingConfig): ?array
    {
        $durationHours = (int) $request->input('duration_hours', 0);
        $durationKey = trim((string) $request->input('duration_key', ''));
        $matchedOption = null;

        if ($durationKey !== '') {
            foreach ($pricingConfig['duration_options'] as $option) {
                if ((string) $option['key'] === $durationKey) {
                    $matchedOption = $option;
                    break;
                }
            }
            if (! $matchedOption) {
                return null;
            }
            $durationHours = (int) $matchedOption['hours'];
        }

        if ($durationHours <= 0) {
            return null;
        }

        if ($durationHours < $pricingConfig['custom_min_hours'] || $durationHours > $pricingConfig['custom_max_hours']) {
            return null;
        }

        if (! $matchedOption) {
            foreach ($pricingConfig['duration_options'] as $option) {
                if ((int) $option['hours'] === $durationHours) {
                    $matchedOption = $option;
                    break;
                }
            }
        }

        $amountToPay = $matchedOption
            ? (float) $matchedOption['amount']
            : round($durationHours * (float) $pricingConfig['rate_per_hour'], 2);

        return [
            'duration_hours' => $durationHours,
            'duration_key' => $matchedOption['key'] ?? null,
            'amount_to_pay' => round(max($amountToPay, 1), 2),
        ];
    }

    private function getOverlappingHireBooking(int $driverId, Carbon $startAt, Carbon $endAt): ?HireBooking
    {
        return HireBooking::where('driver_id', $driverId)
            ->whereIn('status', ['booked', 'accepted', 'ongoing'])
            ->where('start_at', '<', $endAt)
            ->where('end_at', '>', $startAt)
            ->orderBy('end_at')
            ->first();
    }

    private function canDriverRide(AppUser $driver): bool
    {
        if (! ((bool) $driver->recharge_active)) {
            return false;
        }

        if (! $driver->recharge_valid_until) {
            return false;
        }

        $canRide = Carbon::parse($driver->recharge_valid_until)->gte(Carbon::now());
        if (! $canRide && (bool) $driver->recharge_active) {
            $driver->recharge_active = false;
            $driver->save();
        }

        return $canRide;
    }

    private function getProxyContactNumber(): string
    {
        $keys = ['twillio_number', 'sinch_sender_number', 'messagewizard_sender_number', 'general_phone'];
        $meta = GeneralSetting::whereIn('meta_key', $keys)->pluck('meta_value', 'meta_key');

        foreach ($keys as $key) {
            $value = trim((string) ($meta[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function isRazorpayConfiguredForEnvironment(): bool
    {
        return count($this->razorpayMissingFields()) === 0;
    }

    private function razorpayMissingFields(): array
    {
        $environment = (string) (GeneralSetting::getMetaValue('razorpay_options') ?: 'test');
        $keyField = $environment === 'live' ? 'live_razorpay_key_id' : 'test_razorpay_key_id';
        $secretField = $environment === 'live' ? 'live_razorpay_secret_key' : 'test_razorpay_secret_key';

        $values = GeneralSetting::whereIn('meta_key', [$keyField, $secretField])
            ->pluck('meta_value', 'meta_key');

        $missing = [];
        if (trim((string) ($values[$keyField] ?? '')) === '') {
            $missing[] = $keyField;
        }
        if (trim((string) ($values[$secretField] ?? '')) === '') {
            $missing[] = $secretField;
        }

        return $missing;
    }

    public function getVerificationDocuments(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => 'required|exists:app_users,token',
            ]);

            if ($validator->fails()) {
                return $this->errorComputing($validator);
            }

            $user_id = $this->checkUserByToken($request->token);
            if (! $user_id) {
                return $this->addErrorResponse(419, trans('global.token_not_match'), '');
            }

            $user = AppUser::with(['media', 'metadata'])->find($user_id);

            if (! $user) {
                return $this->addErrorResponse(404, trans('global.user_not_found'), '');
            }

            $documentKeys = [
                'driving_licence_front',
                'driving_licence_back',
                'aadhaar_front',
                'aadhaar_back',
                'pan_card',
                'vehicle_insurance_doc',
            ];

            $documentData = [];
            $metaData = $user->metadata->pluck('meta_value', 'meta_key');

            foreach ($documentKeys as $key) {
                $file = $user->getMedia($key)->last();
                $imageUrl = $file ? $file->getUrl() : null;
                $metaKey = "{$key}_status";
                $metaStatus = $metaData[$metaKey] ?? '';
                $documentData[$key] = [
                    "{$key}_image" => $imageUrl,
                    "{$key}_status" => $metaStatus,
                ];
            }

            return $this->addSuccessResponse(200, trans('global.user_documents_fetched_successfully'), $documentData);
        } catch (\Exception $e) {
            return $this->addErrorResponse(500, $e->getMessage(), $e->getMessage());
        }
    }

    public function getDriverComplianceSummary(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => 'required|exists:app_users,token',
            ]);

            if ($validator->fails()) {
                return $this->errorComputing($validator);
            }

            $userId = $this->checkUserByToken($request->token);
            if (! $userId) {
                return $this->addErrorResponse(419, trans('global.token_not_match'), '');
            }

            $user = AppUser::with(['metadata'])->find($userId);
            if (! $user) {
                return $this->addErrorResponse(404, trans('global.user_not_found'), '');
            }

            $statusKeys = [
                'driving_licence_front_status',
                'driving_licence_back_status',
                'aadhaar_front_status',
                'aadhaar_back_status',
                'pan_card_status',
                'vehicle_insurance_doc_status',
            ];

            $metaStatuses = $user->metadata
                ->whereIn('meta_key', $statusKeys)
                ->pluck('meta_value', 'meta_key');

            $statuses = [];
            foreach ($statusKeys as $key) {
                $statuses[$key] = $metaStatuses[$key] ?? '';
            }

            $overallStatus = 'pending';
            if (in_array('rejected', $statuses, true)) {
                $overallStatus = 'rejected';
            } elseif (count(array_filter($statuses, fn ($status) => $status !== 'approved')) === 0) {
                $overallStatus = 'approved';
            }

            return $this->addSuccessResponse(200, 'Captain compliance summary fetched successfully', [
                'verification_document_status' => $overallStatus,
                'documents' => $statuses,
                'host_status' => (string) $user->host_status,
                'document_verify' => (string) $user->document_verify,
            ]);
        } catch (\Exception $e) {
            return $this->addErrorResponse(500, $e->getMessage(), $e->getMessage());
        }
    }
}
