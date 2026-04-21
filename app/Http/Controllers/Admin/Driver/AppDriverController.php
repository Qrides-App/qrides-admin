<?php

namespace App\Http\Controllers\Admin\Driver;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\FirestoreTrait;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Controllers\Traits\NotificationTrait;
use App\Http\Controllers\Traits\UserWalletTrait;
use App\Http\Controllers\Traits\VendorWalletTrait;
use App\Http\Requests\UpdateAppUserRequest;
use App\Models\AllPackage;
use App\Models\AppUser;
use App\Models\AppUserMeta;
use App\Models\GeneralSetting;
use App\Models\HireBooking;
use App\Models\Modern\Item;
use App\Models\Modern\ItemVehicle;
use App\Models\Modern\ItemType;
use App\Models\Payout;
use App\Models\VendorWallet;
use App\Models\VehicleMake;
use App\Services\FirebaseAuthService;
use Carbon\Carbon;
use Gate;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class AppDriverController extends Controller
{
    use FirestoreTrait, MediaUploadingTrait, NotificationTrait, UserWalletTrait, VendorWalletTrait;

    public function index()
    {
        abort_if(Gate::denies('app_user_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $filters = request()->only(['from', 'to', 'status', 'driver', 'host_status']);
        $userType = 'driver';
        $query = AppUser::with(['media', 'metadata', 'item.itemVehicle', 'item.vehicleMake', 'item.subCategory', 'hostBookings'])
            ->where('user_type', $userType)
            ->orderBy('id', 'desc');
        if (isset($filters['from']) && isset($filters['to'])) {
            $query->whereBetween('created_at', [
                date('Y-m-d 00:00:00', strtotime($filters['from'])),
                date('Y-m-d 23:59:59', strtotime($filters['to'])),
            ]);
        } elseif (isset($filters['from'])) {
            $query->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($filters['from'])));
        } elseif (isset($filters['to'])) {
            $query->where('created_at', '<=', date('Y-m-d 23:59:59', strtotime($filters['to'])));
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['host_status'])) {
            $query->where('host_status', $filters['host_status']);
        }

        if (isset($filters['driver'])) {
            $query->where('id', $filters['driver']);
        }

        $appUsers = $query->paginate(20)->appends($filters);

        $searchfield = 'All';
        $searchfieldId = '';
        if (isset($filters['driver']) && $appUsers->count() > 0) {
            $firstUser = $appUsers->first();
            $searchfield = "{$firstUser->first_name} {$firstUser->last_name} ({$firstUser->phone})";
            $searchfieldId = $firstUser->id;
        }

        $statusCountsRaw = AppUser::selectRaw("
    COUNT(*) as total,
    SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as inactive,
    SUM(CASE WHEN host_status = '2' THEN 1 ELSE 0 END) as requested
")->where('user_type', $userType)->first();

        $statusCounts = [
            'live' => $statusCountsRaw->total ?? 0,
            'active' => $statusCountsRaw->active ?? 0,
            'inactive' => $statusCountsRaw->inactive ?? 0,
            'requested' => $statusCountsRaw->requested ?? 0,
            'trash' => AppUser::onlyTrashed()->count(), // this can stay separate
        ];

        return view('admin.appUsers.driver.index', compact('appUsers', 'statusCounts', 'searchfield', 'searchfieldId'));
    }

    public function create()
    {
        abort_if(Gate::denies('app_user_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicleTypes = $this->availableVehicleTypes();

        if ($vehicleTypes->isEmpty()) {
            return redirect()
                ->route('admin.drivers.index')
                ->withErrors(['vehicle_setup' => 'Add at least one active vehicle type before creating a driver.']);
        }

        if ($this->availableVehicleMakes()->isEmpty()) {
            return redirect()
                ->route('admin.drivers.index')
                ->withErrors(['vehicle_setup' => 'Add at least one active vehicle make before creating a driver.']);
        }

        return view('admin.appUsers.driver.create', compact('vehicleTypes'));
    }

    public function store(Request $request)
    {
        abort_if(Gate::denies('app_user_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:app_users,email'],
            'password' => ['required', 'string', 'min:6'],
            'phone_country' => ['required', 'string', 'max:20'],
            'phone' => [
                'required',
                'string',
                'max:25',
                Rule::unique('app_users', 'phone')->where(fn ($query) => $query->where('phone_country', $request->input('phone_country'))),
            ],
            'default_country' => ['nullable', 'string', 'max:10'],
            'status' => ['required', Rule::in(['0', '1'])],
            'host_status' => ['required', Rule::in(['0', '1', '2'])],
            'document_verify' => ['required', Rule::in(['0', '1'])],
            'profile_image' => ['nullable', 'string'],
            'car_type' => ['required', 'exists:item_types,id'],
            'make' => ['required', 'exists:vehicle_makes,id'],
            'model' => ['required', 'string', 'max:255'],
            'year' => ['required', 'digits:4'],
            'registration_number' => ['required', 'string', 'max:255'],
            'vehicle_image' => ['required', 'string'],
            'vehicle_registration_doc' => ['required', 'string'],
            'driving_licence_front' => ['required', 'string'],
            'driving_licence_back' => ['required', 'string'],
            'aadhaar_front' => ['required', 'string'],
            'aadhaar_back' => ['required', 'string'],
            'pan_card' => ['required', 'string'],
            'vehicle_insurance_doc' => ['required', 'string'],
        ]);

        if ($validated['host_status'] === '1' && $validated['document_verify'] !== '1') {
            return back()
                ->withErrors(['host_status' => 'Approve documents before marking this driver as approved.'])
                ->withInput();
        }

        $vehicleType = $this->availableVehicleTypes()->firstWhere('id', $validated['car_type']);
        if (! $vehicleType) {
            return back()
                ->withErrors(['car_type' => 'Selected vehicle type is not active or not available for driver onboarding.'])
                ->withInput();
        }

        $vehicleMake = $this->availableVehicleMakes()->firstWhere('id', $validated['make']);
        if (! $vehicleMake) {
            return back()
                ->withErrors(['make' => 'Selected vehicle make is not active or not available for driver onboarding.'])
                ->withInput();
        }

        $driver = null;

        DB::transaction(function () use ($validated, &$driver) {
            $driver = AppUser::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'] ?? null,
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone_country' => $validated['phone_country'],
                'phone' => $validated['phone'],
                'default_country' => $validated['default_country'] ?? null,
                'status' => $validated['status'],
                'user_type' => 'driver',
                'host_status' => $validated['host_status'],
                'document_verify' => $validated['document_verify'],
                'email_notification' => '1',
                'sms_notification' => '1',
                'push_notification' => '1',
                'verified' => '1',
            ]);

            $this->attachUploadedMedia($driver, 'profile_image', $validated['profile_image'] ?? null);

            foreach ($this->requiredDriverDocuments() as $document) {
                $fileName = $validated[$document['collection']] ?? null;
                $this->attachUploadedMedia($driver, $document['collection'], $fileName);
                $driver->metadata()->updateOrCreate(
                    ['meta_key' => $document['status_key']],
                    ['meta_value' => $validated['document_verify'] === '1' ? 'approved' : 'pending']
                );
            }

            $vehicle = Item::create([
                'token' => strtoupper(Str::random(10)),
                'title' => trim($driver->first_name.' '.($driver->last_name ?? '').' Vehicle'),
                'userid_id' => $driver->id,
                'item_type_id' => $validated['car_type'],
                'module' => 2,
                'status' => $validated['status'],
                'is_verified' => $validated['document_verify'],
                'make' => $validated['make'],
                'model' => $validated['model'],
                'registration_number' => $validated['registration_number'],
            ]);

            ItemVehicle::create([
                'item_id' => $vehicle->id,
                'year' => $validated['year'],
                'vehicle_registration_number' => $validated['registration_number'],
            ]);

            $this->attachUploadedMedia($vehicle, 'front_image', $validated['vehicle_image']);
            $this->attachUploadedMedia($vehicle, 'vehicle_registration_doc', $validated['vehicle_registration_doc']);
            $this->attachUploadedMedia($vehicle, 'vehicle_insurance_doc', $validated['vehicle_insurance_doc']);
        });

        return redirect()
            ->route('admin.driver.account', $driver->id)
            ->with('success', 'Driver created successfully. Account, documents, and vehicle details are ready for review.');
    }

    public function driverAccountView(Request $request, $userId)
    {
        $appUser = AppUser::where('id', $userId)->firstOrFail();
        $packages = AllPackage::pluck('package_name', 'id')->prepend(trans('global.pleaseSelect'), '');
        $appUser->load('package');

        return view('admin.appUsers.driver.account', compact(
            'appUser',
            'userId',
            'packages'
        ));
    }

    public function driverDocumentView(Request $request, $userId)
    {
        $appUser = AppUser::select('id', 'first_name', 'last_name')->where('id', $userId)->firstOrFail();

        return view('admin.appUsers.driver.document', compact(

            'appUser',
            'userId',

        ));
    }

    public function driverPayoutView(Request $request, $userId) {}

    public function driverHireView(Request $request, $userId)
    {
        abort_if(Gate::denies('booking_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $driver = AppUser::select('id', 'first_name', 'last_name', 'phone_country', 'phone')
            ->where('id', $userId)
            ->where('user_type', 'driver')
            ->firstOrFail();

        $filters = $request->only(['status', 'from', 'to']);

        $query = HireBooking::with([
            'driver:id,first_name,last_name,phone',
            'rider:id,first_name,last_name,phone',
        ])->where('driver_id', $driver->id);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['from'])) {
            try {
                $from = Carbon::parse($filters['from'])->startOfDay();
                $query->where('start_at', '>=', $from);
            } catch (\Throwable $e) {
                // Ignore invalid date input and keep the page usable.
            }
        }

        if (! empty($filters['to'])) {
            try {
                $to = Carbon::parse($filters['to'])->endOfDay();
                $query->where('start_at', '<=', $to);
            } catch (\Throwable $e) {
                // Ignore invalid date input and keep the page usable.
            }
        }

        $bookings = $query->orderByDesc('start_at')
            ->paginate(25)
            ->appends($filters);

        $baseStatsQuery = HireBooking::where('driver_id', $driver->id);
        $stats = [
            'total' => (clone $baseStatsQuery)->count(),
            'booked' => (clone $baseStatsQuery)->where('status', 'booked')->count(),
            'ongoing' => (clone $baseStatsQuery)->where('status', 'ongoing')->count(),
            'completed' => (clone $baseStatsQuery)->where('status', 'completed')->count(),
            'cancelled' => (clone $baseStatsQuery)->where('status', 'cancelled')->count(),
        ];

        $currency = GeneralSetting::where('meta_key', 'general_default_currency')
            ->value('meta_value') ?? 'INR';

        $qrPayload = route('scan-to-hire', ['driver_id' => $driver->id]);

        return view('admin.appUsers.driver.hire', compact(
            'driver',
            'qrPayload',
            'currency',
            'filters',
            'stats',
            'bookings'
        ));
    }

    public function driverVehicleView(Request $request, $userId)
    {
        $appUser = AppUser::select('id', 'first_name', 'last_name')->where('id', $userId)->firstOrFail();
        $vehicle = Item::with('itemVehicle')
            ->where('userid_id', $userId)
            ->firstOrFail();

        $itemVehicle = $vehicle->itemVehicle;
        $vehicleType = ItemType::where('module', 2)->get();
        $storeMedia = 'admin.storeMedia';
        $vehicleYear = optional($itemVehicle)->year;
        $vehicleNumber = $vehicle->registration_number;
        $vehicleMake = $vehicle->make;
        $vehicleModel = $vehicle->model;

        return view('admin.appUsers.driver.vehicle', compact(
            'vehicle',
            'appUser',
            'userId',
            'storeMedia',
            'vehicleType',
            'vehicleYear',
            'vehicleNumber',
            'vehicleMake',
            'vehicleModel'
        ));

    }

    public function driverFinanceView(Request $request, $userId)
    {

        $from = request()->input('from');
        $to = request()->input('to');
        $status = request()->input('status');

        $vendor_wallets = VendorWallet::with(['booking:id,token'])
            ->where('vendor_id', $userId)
            ->orderBy('id', 'desc')
            ->paginate(50);

        $userType = $request->query('user_type');
        $appUser = AppUser::select('id', 'first_name', 'last_name')->where('id', $userId)->firstOrFail();
        $general_default_currency = GeneralSetting::where('meta_key', 'general_default_currency')->first();
        $hostspendmoney = number_format($this->getVendorWalletBalance($userId), 2);
        $hostpendingmoney = number_format($this->getTotalWithdrawlForVendor($userId, 'Pending'), 2);
        $hostrecivemoney = number_format($this->getTotalWithdrawlForVendor($userId, 'Success'), 2);
        $totalmoney = number_format($this->getTotalEarningsForVendor($userId), 2);
        $refunded = number_format($this->getTotalRefundForVendor($userId, ''), 2);

        return view('admin.appUsers.driver.finance', compact('userId', 'hostspendmoney', 'hostpendingmoney', 'hostrecivemoney', 'totalmoney', 'refunded', 'vendor_wallets', 'general_default_currency', 'appUser'));
    }

    public function driverProfileView(Request $request, $userId)
    {
        if (! is_numeric($userId)) {
            abort(404, 'Invalid user ID');
        }

        $appUser = AppUser::with(['hostBookings', 'items.itemVehicle', 'items.vehicleMake', 'items.subCategory'])->findOrFail($userId);

        $today = now()->startOfDay();
        $aggregates = $appUser->hostBookings()
            ->selectRaw("
            COUNT(*) as total_rides,
            SUM(status = 'ongoing') as live_rides,
            SUM(status = 'cancelled') as cancelled_rides,
            SUM(status = 'rejected') as rejected_rides,
            SUM(status = 'completed') as completed_rides,
            SUM(CASE WHEN status = 'completed' AND created_at >= ? THEN total ELSE 0 END) as today_earnings,
            SUM(CASE WHEN status = 'completed' AND created_at >= ? THEN admin_commission ELSE 0 END) as admin_commission,
            SUM(CASE WHEN status = 'completed' AND created_at >= ? THEN vendor_commission ELSE 0 END) as driver_earnings,
            SUM(CASE WHEN status = 'completed' AND payment_method = 'cash' AND created_at >= ? THEN vendor_commission ELSE 0 END) as cash_earnings,
            SUM(CASE WHEN status = 'completed' AND payment_method != 'cash' AND created_at >= ? THEN vendor_commission ELSE 0 END) as online_earnings
        ", [$today, $today, $today, $today, $today])
            ->first();

        $vehicle = $appUser->items->first();
        $general_default_currency = cache()->remember('general_default_currency', now()->addHours(24), fn () => View::shared('general_default_currency'));

        $data = [
            'live_rides' => (int) ($aggregates->live_rides ?? 0),
            'cancelled_rides' => (int) ($aggregates->cancelled_rides ?? 0),
            'rejected_rides' => (int) ($aggregates->rejected_rides ?? 0),
            'completed_rides' => (int) ($aggregates->completed_rides ?? 0),
            'total_rides' => (int) ($aggregates->total_rides ?? 0),
            'today_earnings' => number_format($aggregates->today_earnings ?? 0, 2, '.', ''),
            'admin_commission' => number_format($aggregates->admin_commission ?? 0, 2, '.', ''),
            'driver_earnings' => number_format($aggregates->driver_earnings ?? 0, 2, '.', ''),
            'cash_earnings' => number_format($aggregates->cash_earnings ?? 0, 2, '.', ''),
            'online_earnings' => number_format($aggregates->online_earnings ?? 0, 2, '.', ''),
            'vehicle_make' => $vehicle->vehicleMake->name ?? 'N/A',
            'vehicle_model' => $vehicle->model ?? 'N/A',
            'vehicle_verified' => $vehicle ? $vehicle->status : 'N/A',
            'vehicle_registration_number' => $vehicle->registration_number ?? 'N/A',
            'vehicle_year' => $vehicle?->itemVehicle?->year ?? 'N/A',
        ];

        return view('admin.appUsers.driver.profile', compact('appUser', 'data', 'userId', 'general_default_currency'));
    }

    public function updateProfile(UpdateAppUserRequest $request, $host_id)
    {
        $appUser = AppUser::findOrFail($host_id);
        $userEmail = AppUser::where('email', $request->email)->first();
        if ($userEmail && $userEmail->id !== $appUser->id) {
            return redirect()->to("admin/driver/account/{$appUser->id}")
                ->withErrors(['email' => 'Email already exists.']);
        }
        $userPhone = AppUser::where('phone', $request->phone)->first();
        if ($userPhone && $userPhone->id !== $appUser->id) {
            return redirect()->to("admin/driver/account/{$appUser->id}")
                ->withErrors(['phone' => 'Phone number already exists.']);
        }
        $data = $request->except(['password', 'host_status', 'user_type']);
        if (! empty($request->input('password'))) {
            $data['password'] = Hash::make($request->input('password'));
        }
        $appUser->update($data);
        if ($request->input('profile_image', false)) {
            if (! $appUser->profile_image || $request->input('profile_image') !== $appUser->profile_image->file_name) {
                if ($appUser->profile_image) {
                    $appUser->profile_image->delete();
                }
                $appUser->addMedia(storage_path('tmp/uploads/'.basename($request->input('profile_image'))))
                    ->toMediaCollection('profile_image');
            }
        } elseif ($appUser->profile_image) {
            $appUser->profile_image->delete();
        }

        return redirect()->to("admin/driver/account/{$appUser->id}")
            ->with('success', 'Profile updated successfully.');
    }

    public function profileVerify(Request $request, $host_id)
    {
        return $this->updateStatusField($host_id, 'status', $request->input('status'));
    }

    public function emailVerify(Request $request, $host_id)
    {
        return $this->updateStatusField($host_id, 'email_verify', $request->input('email_verify'));
    }

    public function documentVerify(Request $request, $host_id, FirebaseAuthService $firebaseAuthService)
    {
        $verified = $request->input('document_verify');
        $this->updateStatusField($host_id, 'document_verify', $verified);
        $user = AppUser::find($host_id);
        if (! $user) {
            return response()->json(['message' => 'User not found.'], 404);
        }
        if (! $user->firestore_id) {
            $firestoreData = $this->generateDriverFirestoreData($user);
            $firestoreDoc = $this->storeDriverInFirestore($firestoreData);
            $firestoreDocId = $firestoreDoc->id();
            $user->update(['firestore_id' => $firestoreDocId]);
            $user['firestore_id'] = $firestoreDocId;
        }
        $user->update(['host_status' => $verified ? 1 : 0]);
        $this->updateDocument('drivers', $user->firestore_id, [
            'docApprovedStatus' => $verified ? 'approved' : 'rejected',
            'driverId' => $user->id,
        ]);

        return response()->json([
            'message' => 'Document verification '.($verified ? 'approved' : 'rejected').' successfully.',
        ]);
    }

    public function phoneVerify(Request $request, $host_id)
    {
        return $this->updateStatusField($host_id, 'phone_verify', $request->input('phone_verify'));
    }

    private function updateStatusField($driver_id, $field, $value)
    {
        $user = AppUser::findOrFail($driver_id);
        $user->{$field} = $value;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => __('global.status_updated_successfully'),
            'data' => [
                'id' => $user->id,
                'field' => $field,
                'value' => $value,
            ],
        ]);
    }

    private function attachUploadedMedia($model, string $collection, ?string $fileName): void
    {
        if (blank($fileName)) {
            return;
        }

        $path = storage_path('tmp/uploads/'.basename($fileName));
        if (! file_exists($path)) {
            return;
        }

        $model->addMedia($path)->toMediaCollection($collection);
    }

    private function availableVehicleTypes()
    {
        return ItemType::query()
            ->where('module', 2)
            ->where('status', '1')
            ->orderBy('name')
            ->get();
    }

    private function availableVehicleMakes()
    {
        return VehicleMake::query()
            ->where('module', 2)
            ->where('status', '1')
            ->orderBy('name')
            ->get();
    }

    public function getVerificationDocuments(Request $request)
    {
        try {
            $user = AppUser::find($request->user_id);

            if (! $user) {
                return response()->json(['status' => false, 'message' => 'User not found.'], 404);
            }

            $documentFields = [
                'driving_licence_front',
                'driving_licence_back',
                'driver_id_front',
                'driver_id_back',
            ];

            $documents = [];

            foreach ($documentFields as $field) {
                $media = $user->getFirstMedia($field);

                $image = $media ? $media->getUrl() : null;
                $createdAt = $media ? $media->created_at : null;

                $status = AppUserMeta::where('user_id', $user->id)
                    ->where('meta_key', $field.'_status')
                    ->value('meta_value') ?? ($media ? 'pending' : 'not_uploaded');

                $documents[$field] = [
                    'image' => $image,
                    'status' => $status,
                    'created_at' => $createdAt,
                ];
            }

            return response()->json([
                'status' => true,
                'message' => 'User documents retrieved successfully.',
                'data' => [
                    'user_id' => $user->id,
                    'documents' => $documents,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateVerificationDocumentStatus(Request $request)
    {
        try {
            $user = AppUser::where('id', $request->user_id)->first();

            if (! $user) {
                return response()->json(['success' => false, 'message' => trans('global.user_not_found')], 404);
            }

            AppUserMeta::updateOrCreate(
                ['user_id' => $user->id, 'meta_key' => $request->meta_key.'_status'],
                ['meta_value' => $request->status]
            );

            return response()->json([
                'success' => true,
                'message' => trans('global.status_updated_successfully'),
                'status' => $request->status,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function searchDrivers(Request $request)
    {
        $searchTerm = $request->input('q');

        // Return an empty array if search term is empty
        if (empty($searchTerm)) {
            return response()->json([]);
        }

        $drivers = AppUser::where('user_type', 'driver')
            ->where(function ($query) use ($searchTerm) {
                $query->where('first_name', 'like', '%'.$searchTerm.'%')
                    ->orWhere('last_name', 'like', '%'.$searchTerm.'%')
                    ->orWhere('phone', 'like', '%'.$searchTerm.'%')
                    ->orWhere('email', 'like', '%'.$searchTerm.'%');
            })
            ->select('id', 'first_name', 'last_name', 'phone')
            ->get();

        $data = $drivers->map(function ($driver) {
            return [
                'id' => $driver->id,
                'name' => $driver->first_name.' '.$driver->last_name.' ('.$driver->phone.')',
                'first_name' => $driver->first_name,
            ];
        });

        return response()->json($data);
    }

    public function driverPayoutsView(Request $request, $driver_id)
    {

        abort_if(Gate::denies('payout_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $from = request()->input('from');
        $to = request()->input('to');
        $status = request()->input('status');
        $appUser = AppUser::where('id', $driver_id)->first();
        $query = Payout::with('vendor')
            ->where('vendorid', $driver_id);
        $isFiltered = ($from || $to || $status);
        if ($from && $to) {
            $query->where(function ($query) use ($from, $to) {
                $query->whereBetween('payouts.created_at', [$from.' 00:00:00', $to.' 23:59:59'])
                    ->orWhereBetween('payouts.updated_at', [$from.' 00:00:00', $to.' 23:59:59']);
            });
        } elseif ($from) {
            $query->where(function ($query) use ($from) {
                $query->where('payouts.created_at', '>=', $from.' 00:00:00')
                    ->orWhere('payouts.updated_at', '>=', $from.' 00:00:00');
            });
        } elseif ($to) {
            $query->where(function ($query) use ($to) {
                $query->where('payouts.created_at', '<=', $to.' 23:59:59')
                    ->orWhere('payouts.updated_at', '<=', $to.' 23:59:59');
            });
        }

        if ($status !== null) {
            $query->where('payout_status', $status);
        }
        $payouts = $isFiltered ? $query->paginate(50) : $query->paginate(50);

        return view('admin.appUsers.driver.payouts', compact('payouts', 'appUser', 'driver_id'));
    }
}
