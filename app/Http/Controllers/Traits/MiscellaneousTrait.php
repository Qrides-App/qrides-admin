<?php

namespace App\Http\Controllers\Traits;

use App\Models\AddCoupon;
use App\Models\AppUser;
use App\Models\AppUserMeta;
use App\Models\Booking;
use App\Models\CancellationPolicy;
use App\Models\GeneralSetting;
use App\Models\Modern\Item;
use App\Models\Modern\ItemMeta;
use App\Models\Modern\ItemType;
use App\Models\Modern\ItemVehicle;
use App\Models\SubCategory;
use App\Models\VehicleMake;
use App\Models\VehicleOdometer;
use Carbon\Carbon;
use DB;

trait MiscellaneousTrait
{
    /**
     * Format the item data with front image.
     *
     * @param  \App\Models\Item  $item
     * @return array
     */
    public function formatItemData($itemDetail, $convertionRate = 1)
    {
        $frontImage = $itemDetail->front_image;
        $frontImageUrl = $frontImage ? $frontImage->thumbnail : null;

        return [
            'id' => $itemDetail->id,
            'name' => $itemDetail->title,
            'item_rating' => (string) $itemDetail->item_rating,
            'address' => $itemDetail->address,
            'state_region' => $itemDetail->state_region,
            'city' => $itemDetail->state_region,
            'zip_postal_code' => (string) $itemDetail->zip_postal_code,
            'price' => (string) $itemDetail->price,
            'latitude' => $itemDetail->latitude,
            'longitude' => $itemDetail->longitude,
            'status' => (string) $itemDetail->status,
            'item_type_id' => (string) $itemDetail->item_type_id,
            'image' => $frontImageUrl,
            'item_info' => ItemMeta::getMetaValue($itemDetail->id, 'itemMetaInfo'),
        ];
    }

    public function checkUserByToken($token)
    {

        $tokendata = AppUser::where('token', trim($token))->where('status', 1)->first();

        if ($tokendata) {
            return $tokendata->id;
        } else {
            return '';
        }
    }

    public function getGeneralSettingValue($key)
    {
        $setting = GeneralSetting::where('meta_key', $key)
            ->first();

        if ($setting) {
            return $setting->meta_value;
        }

        return null;
    }

    public function getItemPricesDetails($itemTypeId, $distance, $couponCode = null, $walletAmount = 0, $selectedCurrencyCode = 'USD', $conversionRate = 1, $durationMinutes = 0, $surgeMultiplier = 1.0, array $fareExtras = [])
    {
        try {
            $itemType = ItemType::with('cityFare')->findOrFail($itemTypeId);
            $perKmRate = (float) (optional($itemType->cityFare)->recommended_fare ?? 0);

            // General/global fallbacks
            $baseFare = (float) (GeneralSetting::getMetaValue('fare_base') ?? 30);
            $includedKm = (float) (GeneralSetting::getMetaValue('fare_included_km') ?? 3);
            $perMinRate = (float) (GeneralSetting::getMetaValue('fare_per_min') ?? 0);
            $minFare = (float) (GeneralSetting::getMetaValue('fare_min_fare') ?? $baseFare);
            $bookingFee = (float) (GeneralSetting::getMetaValue('fare_booking_fee') ?? 0);
            $pickupIncludedKm = (float) (GeneralSetting::getMetaValue('fare_pickup_included_km') ?? 1);
            $pickupPerKm = (float) (GeneralSetting::getMetaValue('fare_pickup_per_km') ?? $perKmRate);
            $waitingPerMin = (float) (GeneralSetting::getMetaValue('fare_waiting_per_min') ?? 0);
            $taxPercent = (float) (GeneralSetting::getMetaValue('fare_tax_percent') ?? 0);
            $airportFeeDefault = (float) (GeneralSetting::getMetaValue('fare_airport_fee') ?? 0);
            $surgeCap = (float) (GeneralSetting::getMetaValue('fare_surge_cap') ?? 2.0);
            $surgeFloor = (float) (GeneralSetting::getMetaValue('fare_surge_floor') ?? 1.0);
            $dynamicEnabled = filter_var((GeneralSetting::getMetaValue('fare_dynamic_surge_enabled') ?? '1'), FILTER_VALIDATE_BOOLEAN);
            $dynamicWindowMin = max(1, (int) (GeneralSetting::getMetaValue('fare_dynamic_surge_window_min') ?? 15));
            $dynamicSensitivity = (float) (GeneralSetting::getMetaValue('fare_dynamic_surge_sensitivity') ?? 0.35);
            $dynamicMin = max(0.1, (float) (GeneralSetting::getMetaValue('fare_dynamic_surge_min') ?? 1.0));
            $dynamicMax = max($dynamicMin, (float) (GeneralSetting::getMetaValue('fare_dynamic_surge_max') ?? 1.8));
            $timeSurgeRulesJson = GeneralSetting::getMetaValue('fare_time_surge_rules');
            $timeSurgeRules = $timeSurgeRulesJson ? json_decode($timeSurgeRulesJson, true) : [];
            $weatherRules = $this->parseJsonSettingAsArray('fare_weather_multipliers_json');
            $eventRules = $this->parseJsonSettingAsArray('fare_event_multipliers_json');

            // time-based surge (server-side)
            $now = Carbon::now();
            $dow = (int) $now->dayOfWeek; // 0=Sun
            $timeSurge = 1.0;
            if (is_array($timeSurgeRules)) {
                foreach ($timeSurgeRules as $rule) {
                    if (! isset($rule['days'], $rule['start'], $rule['end'], $rule['multiplier'])) {
                        continue;
                    }
                    if (! in_array($dow, $rule['days'])) {
                        continue;
                    }
                    $start = Carbon::createFromFormat('H:i', $rule['start'], $now->timezone);
                    $end = Carbon::createFromFormat('H:i', $rule['end'], $now->timezone);
                    $inWindow = $start <= $end
                        ? ($now->between($start, $end))
                        : ($now >= $start || $now <= $end); // spans midnight
                    if ($inWindow) {
                        $timeSurge = max($timeSurge, (float) $rule['multiplier']);
                    }
                }
            }

            $manualSurge = max(0.1, (float) $surgeMultiplier);
            $cityId = isset($fareExtras['pickup_city_id']) ? (int) $fareExtras['pickup_city_id'] : null;
            $weatherCondition = isset($fareExtras['weather_condition']) ? strtolower(trim((string) $fareExtras['weather_condition'])) : null;
            $eventKey = isset($fareExtras['event_key']) ? trim((string) $fareExtras['event_key']) : null;

            $demandSupplyContext = $dynamicEnabled
                ? $this->calculateDemandSupplySurgeContext($dynamicWindowMin, $dynamicSensitivity, $dynamicMin, $dynamicMax, $cityId)
                : ['multiplier' => 1.0, 'demand_count' => 0, 'available_drivers' => 0, 'busy_drivers' => 0, 'supply_count' => 0, 'ratio' => 0];

            $demandSupplySurge = (float) ($demandSupplyContext['multiplier'] ?? 1.0);
            $weatherSurge = $dynamicEnabled ? $this->resolveWeatherSurge($weatherRules, $weatherCondition) : 1.0;
            $eventSurge = $dynamicEnabled ? $this->resolveEventSurge($eventRules, $eventKey, $now) : 1.0;

            // combine client surge (traffic/supply), time-based and server dynamic multipliers
            $surgeMultiplier = $manualSurge * (float) $timeSurge * $demandSupplySurge * $weatherSurge * $eventSurge;

            // clamp surge
            $surgeMultiplier = max($surgeFloor, min($surgeCap, (float) $surgeMultiplier));

            $billableKm = max(0, $distance - $includedKm);
            $distanceCost = $billableKm * $perKmRate;
            $timeCost = max(0, $durationMinutes) * $perMinRate;

            $subtotal = $baseFare + $distanceCost + $timeCost;
            $surgedSubtotal = $subtotal * $surgeMultiplier;
            $rideFareAfterSurge = max($minFare, $surgedSubtotal);

            $pickupDistanceKm = max(0, (float) ($fareExtras['pickup_distance_km'] ?? 0));
            $waitingMinutes = max(0, (float) ($fareExtras['waiting_minutes'] ?? 0));
            $tollCharge = max(0, (float) ($fareExtras['toll_charge'] ?? 0));
            $parkingCharge = max(0, (float) ($fareExtras['parking_charge'] ?? 0));
            $airportFee = max(0, (float) ($fareExtras['airport_fee'] ?? $airportFeeDefault));
            $applyAirportFee = array_key_exists('apply_airport_fee', $fareExtras)
                ? filter_var($fareExtras['apply_airport_fee'], FILTER_VALIDATE_BOOLEAN)
                : true;
            if (! $applyAirportFee) {
                $airportFee = 0;
            }

            $pickupBillableKm = max(0, $pickupDistanceKm - $pickupIncludedKm);
            $pickupCharge = $pickupBillableKm * $pickupPerKm;
            $waitingCharge = $waitingMinutes * $waitingPerMin;

            $priceBeforeDiscount = $rideFareAfterSurge + $bookingFee + $pickupCharge + $waitingCharge + $tollCharge + $parkingCharge + $airportFee;

            $totalPrice = $priceBeforeDiscount;
            $couponDiscount = 0;

            if ($couponCode) {
                $coupon = AddCoupon::where('coupon_code', $couponCode)->first();
                if ($coupon && $totalPrice >= $coupon->min_order_amount) {
                    if (in_array($coupon->coupon_type, ['percentage', 'percent'], true)) {
                        $couponDiscount = ($totalPrice * $coupon->coupon_value) / 100;
                    } else {
                        $couponDiscount = $coupon->coupon_value;
                    }
                    $totalPrice -= $couponDiscount;
                }
            }

            $totalPrice = max(0, $totalPrice);
            $taxAmount = $totalPrice * ($taxPercent / 100);
            $totalPrice += $taxAmount;
            $totalPrice = max(0, $totalPrice);
            $walletApplied = min($walletAmount, $totalPrice);
            $remainingWallet = $walletAmount - $walletApplied;
            $grossPrice = $totalPrice - $walletApplied;

            $response = [
                'distance' => $distance,
                'included_km' => $includedKm,
                'price_per_km' => $this->formatPriceWithConversion($perKmRate, $selectedCurrencyCode, $conversionRate),
                'price_per_min' => $this->formatPriceWithConversion($perMinRate, $selectedCurrencyCode, $conversionRate),
                'base_fare' => $this->formatPriceWithConversion($baseFare, $selectedCurrencyCode, $conversionRate),
                'ride_fare_after_surge' => $this->formatPriceWithConversion($rideFareAfterSurge, $selectedCurrencyCode, $conversionRate),
                'booking_fee' => $this->formatPriceWithConversion($bookingFee, $selectedCurrencyCode, $conversionRate),
                'pickup_charge' => $this->formatPriceWithConversion($pickupCharge, $selectedCurrencyCode, $conversionRate),
                'waiting_charge' => $this->formatPriceWithConversion($waitingCharge, $selectedCurrencyCode, $conversionRate),
                'toll_charge' => $this->formatPriceWithConversion($tollCharge, $selectedCurrencyCode, $conversionRate),
                'parking_charge' => $this->formatPriceWithConversion($parkingCharge, $selectedCurrencyCode, $conversionRate),
                'airport_fee' => $this->formatPriceWithConversion($airportFee, $selectedCurrencyCode, $conversionRate),
                'tax_percent' => $taxPercent,
                'tax_amount' => $this->formatPriceWithConversion($taxAmount, $selectedCurrencyCode, $conversionRate),
                'manual_surge_multiplier' => $manualSurge,
                'time_surge_multiplier' => $timeSurge,
                'demand_supply_surge_multiplier' => $demandSupplySurge,
                'weather_surge_multiplier' => $weatherSurge,
                'event_surge_multiplier' => $eventSurge,
                'dynamic_surge_enabled' => $dynamicEnabled,
                'surge_multiplier' => $surgeMultiplier,
                'surge_context' => [
                    'window_minutes' => $dynamicWindowMin,
                    'demand_count' => (int) ($demandSupplyContext['demand_count'] ?? 0),
                    'supply_count' => (int) ($demandSupplyContext['supply_count'] ?? 0),
                    'busy_drivers' => (int) ($demandSupplyContext['busy_drivers'] ?? 0),
                    'available_drivers' => (int) ($demandSupplyContext['available_drivers'] ?? 0),
                    'demand_supply_ratio' => round((float) ($demandSupplyContext['ratio'] ?? 0), 3),
                    'weather_condition' => $weatherCondition,
                    'event_key' => $eventKey,
                ],
                'price_before_discount' => $this->formatPriceWithConversion($priceBeforeDiscount, $selectedCurrencyCode, $conversionRate),
                'coupon_discount' => $this->formatPriceWithConversion($couponDiscount, $selectedCurrencyCode, $conversionRate),
                'wallet_amount' => $this->formatPriceWithConversion($walletApplied, $selectedCurrencyCode, $conversionRate),
                'remaining_wallet_balance' => $this->formatPriceWithConversion($remainingWallet, $selectedCurrencyCode, $conversionRate),
                'gross_price' => $this->formatPriceWithConversion($grossPrice, $selectedCurrencyCode, $conversionRate),
                'total_price' => $this->formatPriceWithConversion($totalPrice, $selectedCurrencyCode, $conversionRate),
                'coupon_code' => $couponCode,
                'pricing_type' => 'Distance + Time + Auto Dynamic Surge + Platform Fees',
            ];

            return $this->addSuccessResponse(200, 'Item pricing calculated successfully.', $response);
        } catch (\Exception $e) {
            return $this->addErrorResponse(500, 'Internal server error.', $e->getMessage());
        }
    }

    private function parseJsonSettingAsArray(string $metaKey): array
    {
        $raw = GeneralSetting::getMetaValue($metaKey);
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function calculateDemandSupplySurgeContext(int $windowMinutes, float $sensitivity, float $minMultiplier, float $maxMultiplier, ?int $cityId = null): array
    {
        if (! DB::getSchemaBuilder()->hasTable('bookings') || ! DB::getSchemaBuilder()->hasTable('app_users')) {
            return ['multiplier' => 1.0, 'demand_count' => 0, 'supply_count' => 0, 'busy_drivers' => 0, 'available_drivers' => 0, 'ratio' => 0];
        }

        $windowStart = Carbon::now()->subMinutes($windowMinutes);

        $activeBookingStatuses = ['Pending', 'Accepted', 'Ongoing', 'Arrived'];
        $demandQuery = DB::table('bookings')->whereIn('status', $activeBookingStatuses);
        if (DB::getSchemaBuilder()->hasColumn('bookings', 'module')) {
            $demandQuery->where('module', 2);
        }
        if (DB::getSchemaBuilder()->hasColumn('bookings', 'created_at')) {
            $demandQuery->where('created_at', '>=', $windowStart);
        }
        if (DB::getSchemaBuilder()->hasColumn('bookings', 'deleted_at')) {
            $demandQuery->whereNull('deleted_at');
        }

        $demandCount = (int) $demandQuery->count();

        $supplyQuery = DB::table('app_users')->where('user_type', 'driver');
        if (DB::getSchemaBuilder()->hasColumn('app_users', 'status')) {
            $supplyQuery->where('status', 1);
        }
        if (DB::getSchemaBuilder()->hasColumn('app_users', 'host_status')) {
            $supplyQuery->where('host_status', 1);
        }
        if (DB::getSchemaBuilder()->hasColumn('app_users', 'document_verify')) {
            $supplyQuery->where('document_verify', 1);
        }
        if (DB::getSchemaBuilder()->hasColumn('app_users', 'deleted_at')) {
            $supplyQuery->whereNull('deleted_at');
        }

        $supplyCount = (int) $supplyQuery->count();

        $busyDriversQuery = DB::table('bookings')
            ->whereIn('status', ['Accepted', 'Ongoing', 'Arrived'])
            ->whereNotNull('host_id');
        if (DB::getSchemaBuilder()->hasColumn('bookings', 'module')) {
            $busyDriversQuery->where('module', 2);
        }
        if (DB::getSchemaBuilder()->hasColumn('bookings', 'deleted_at')) {
            $busyDriversQuery->whereNull('deleted_at');
        }

        $busyDrivers = (int) $busyDriversQuery->distinct('host_id')->count('host_id');
        $availableDrivers = max(1, $supplyCount - $busyDrivers);
        $ratio = $demandCount / $availableDrivers;

        $rawMultiplier = 1 + (($ratio - 1) * max(0, $sensitivity));
        $multiplier = max($minMultiplier, min($maxMultiplier, $rawMultiplier));

        return [
            'multiplier' => $multiplier,
            'demand_count' => $demandCount,
            'supply_count' => $supplyCount,
            'busy_drivers' => $busyDrivers,
            'available_drivers' => $availableDrivers,
            'ratio' => $ratio,
        ];
    }

    private function resolveWeatherSurge(array $rules, ?string $weatherCondition): float
    {
        if (! $weatherCondition || empty($rules)) {
            return 1.0;
        }

        $normalized = strtolower($weatherCondition);
        if (array_key_exists($normalized, $rules) && is_numeric($rules[$normalized])) {
            return max(0.1, (float) $rules[$normalized]);
        }

        foreach ($rules as $rule) {
            if (! is_array($rule)) {
                continue;
            }
            $key = strtolower((string) ($rule['condition'] ?? $rule['key'] ?? ''));
            if ($key !== '' && $key === $normalized) {
                $multiplier = (float) ($rule['multiplier'] ?? 1);

                return max(0.1, $multiplier);
            }
        }

        return 1.0;
    }

    private function resolveEventSurge(array $rules, ?string $eventKey, Carbon $now): float
    {
        if (empty($rules)) {
            return 1.0;
        }

        $activeMultiplier = 1.0;
        foreach ($rules as $rule) {
            if (! is_array($rule)) {
                continue;
            }
            $multiplier = max(0.1, (float) ($rule['multiplier'] ?? 1));
            $ruleKey = trim((string) ($rule['key'] ?? $rule['name'] ?? ''));

            if ($eventKey && $ruleKey !== '' && strcasecmp($ruleKey, $eventKey) === 0) {
                $activeMultiplier = max($activeMultiplier, $multiplier);
                continue;
            }

            $startAt = isset($rule['start_at']) ? Carbon::parse($rule['start_at']) : null;
            $endAt = isset($rule['end_at']) ? Carbon::parse($rule['end_at']) : null;
            if ($startAt && $endAt && $now->between($startAt, $endAt)) {
                $activeMultiplier = max($activeMultiplier, $multiplier);
            }
        }

        return $activeMultiplier;
    }

    public function parseDataFromResponse($responseString)
    {
        // Use regular expression to extract JSON data.
        $pattern = '/\{(?:[^{}]|(?R))*\}/';
        if (preg_match($pattern, $responseString, $matches)) {
            $jsonData = $matches[0];

            $data = json_decode($jsonData, true);

            if ($data !== null && isset($data['data'])) {
                return $data['data'];
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    public function rollbackItemAvailability($itemId, $checkIn, $checkOut)
    {

        DB::beginTransaction();
        $dates = [];
        $currentDate = Carbon::parse($checkIn);
        $endDate = Carbon::parse($checkOut);

        while ($currentDate < $endDate) {
            $dates[] = $currentDate->toDateString();
            $currentDate->addDay();
        }

        DB::commit();
    }

    public function convertFormattedNumber($value, $commaAsDecimalPoint = false)
    {
        if ($commaAsDecimalPoint) {
            $value = str_replace(',', '.', $value);
            $value = str_replace('.', '', $value);
            $count = substr_count($value, '.');
            if ($count > 1) {
                $value = substr_replace($value, '', strrpos($value, '.'), 1);
            }

            return (float) $value;
        } else {
            return (float) str_replace(',', '', $value);
        }
    }

    public function getModuleIdOrDefault($request, $default = 1)
    {

        return $request->input('module_id', $default);
    }

    public function insertItemMetaData($request, $module, $id)
    {
        $metaData = json_decode($request->input('metaData'));
        $selectedRules = [];
        // if (isset($metaData->rules)) {
        //     $selectedRules = implode(',', $metaData->rules);
        // }
        $selectedRules = isset($metaData->rules) && is_array($metaData->rules) ? implode(',', $metaData->rules) : null;

        switch ($module) {

            case 2:
                $data = [
                    'rules' => $selectedRules ?? null,
                ];

                $itemVehicleData = [
                    'year' => $metaData->year ?? null,
                    'odometer' => $metaData->odometer ?? null,
                    'vehicle_registration_number' => $metaData->vehicle_registration_number ?? null,
                ];

                $identifier = [
                    'item_id' => $id,
                ];

                $itemVehicle = ItemVehicle::updateOrCreate($identifier, $itemVehicleData);

                $Vechile = Item::where('id', $id)->first();

                if ($Vechile) {
                    $Vechile->update([
                        'category_id' => $metaData->category_id,
                        'subcategory_id' => $metaData->subcategory_id,
                        'service_type' => $metaData->service_type,
                    ]);
                }

                $this->addOrUpdateItemMeta($id, $data);
                break;

            default:
                return []; // Add a default case to return an empty array if module doesn't match any case
        }
    }

    public function returnItemMetaData($id, $module)
    {

        switch ($module) {
            case 1:
                $item = Item::where('id', $id)->first();
                $data = [
                    'cleaning_fee' => ItemMeta::getMetaValue($id, 'cleaning_fee') ?? 0,
                    'additional_fee' => ItemMeta::getMetaValue($id, 'additional_fee') ?? 0,
                    'security_fee' => ItemMeta::getMetaValue($id, 'security_fee') ?? 0,
                    'service_type' => $item->service_type ?? '',
                    'weekend_price' => ItemMeta::getMetaValue($id, 'weekend_price') ?? 0,
                    'rules' => array_map('intval', explode(',', ItemMeta::getMetaValue($id, 'rules'))) ?? null,
                ];

                break;

            case 2:

                $Vechile = Item::where('id', $id)->first();
                $itemVehicle = ItemVehicle::where('item_id', $id)->first();
                $data = [
                    'year' => $itemVehicle->year ?? null,
                    'odometer' => $itemVehicle->odometer ?? null,
                    'doorStep_price' => ItemMeta::getMetaValue($id, 'doorStep_price') ?? 0,
                    'security_fee' => ItemMeta::getMetaValue($id, 'security_fee') ?? 0,
                    'rules' => array_map('intval', explode(',', ItemMeta::getMetaValue($id, 'rules'))) ?? null,
                    'service_type' => $Vechile->service_type ?? '',
                    'category_id' => $Vechile->category_id ?? null,
                    'subcategory_id' => $Vechile->subcategory_id ?? null,
                ];

                break;

            case 3:
                $item = Item::where('id', $id)->first();
                $data = [
                    'boat_length' => ItemMeta::getMetaValue($id, 'boat_length') ?? null,
                    'year' => ItemMeta::getMetaValue($id, 'year') ?? null,
                    'service_type' => $item->service_type ?? '',
                    'rules' => array_map('intval', explode(',', ItemMeta::getMetaValue($id, 'rules'))) ?? null,
                ];

                break;

            case 4:
                $data = [
                    'price_per_week' => ItemMeta::getMetaValue($id, 'price_per_week') ?? null,
                    'price_per_day' => ItemMeta::getMetaValue($id, 'price_per_day') ?? null,
                    'additional_hour_price' => ItemMeta::getMetaValue($id, 'additional_hour_price') ?? null,
                    'parking_extrance' => ItemMeta::getMetaValue($id, 'parking_extrance') ?? null,
                    'number_of_parking_slots' => ItemMeta::getMetaValue($id, 'number_of_parking_slots') ?? null,
                    'enable_parking_slot' => ItemMeta::getMetaValue($id, 'enable_parking_slot') ?? null,
                    'rules' => array_map('intval', explode(',', ItemMeta::getMetaValue($id, 'rules'))) ?? null,
                ];

                break;

            case 5:
                $BookableData = Item::where('id', $id)->first();

                $data = [
                    'style_note' => ItemMeta::getMetaValue($id, 'style_note') ?? null,
                    'rules' => array_map('intval', explode(',', ItemMeta::getMetaValue($id, 'rules'))) ?? null,
                    'category_id' => $BookableData->category_id ?? null,
                    'service_type' => $BookableData->service_type ?? '',
                    'security_fee' => ItemMeta::getMetaValue($id, 'security_fee') ?? 0,
                    'subcategory_id' => $BookableData->subcategory_id ?? null,
                ];

                break;

            case 6:
                $data = [
                    'hours_discount' => ItemMeta::getMetaValue($id, 'hours_discount') ?? null,
                    'working_hour_list' => json_decode(ItemMeta::getMetaValue($id, 'working_hour_list')) ?? null,
                    'cleaning_fees' => ItemMeta::getMetaValue($id, 'cleaning_fees') ?? null,
                    'rules' => array_map('intval', explode(',', ItemMeta::getMetaValue($id, 'rules'))) ?? null,
                ];

                break;

            default:
                return [];
        }

        return json_encode($data);
    }

    public function getModuleInfoValues($moduleId = '', $id = null, $request = null)
    {
        $metaData = [];
        $itemDetail = Item::find($id);
        $playerIdMeta = $itemDetail->appUser->metadata->firstWhere('meta_key', 'player_id');
        if (isset($itemDetail->appUser->profile_image->preview_url)) {
            $host_profile_image = $itemDetail->appUser->profile_image->preview_url;
        } else {
            $host_profile_image = null;
        }

        $galleryImages = $itemDetail->gallery;

        $galleryImageUrls = [];
        foreach ($galleryImages as $image) {
            $galleryImageUrls[] = $image->url;
        }

        if (! $itemDetail) {
            return null;
        }
        $distance = '';
        if ($request && $request->has(['latitude', 'longitude'])) {
            $userLatitude = $request->input('latitude');
            $userLongitude = $request->input('longitude');
            $distance = $this->calculateDistance($userLatitude, $userLongitude, $itemDetail->latitude, $itemDetail->longitude);
            $distance .= ' km';
        }

        $moduleId = $itemDetail->module ?? 1;
        $rules = array_map('intval', explode(',', ItemMeta::getMetaValue($id, 'rules')));
        $metaData['distance'] = $distance;

        // $itemMetaData = ItemMeta::where('rental_item_id', $id)
        //     ->whereIn('meta_key', ['weekly_discount', 'weekly_discount_type', 'monthly_discount', 'monthly_discount_type', 'doorStep_price'])
        //     ->get()
        //     ->keyBy('meta_key');

        $reviewData = [];
        foreach ($itemDetail->reviews as $review) {

            if (empty($review->guest->profile_image->url)) {
                $profile_image = 'null';
            } else {
                $profile_image = $review->guest->profile_image->url;
            }
            $reviewData[] = [
                'id' => $review->id,
                'booking_id' => (string) $review->bookingid,
                'guest_id' => (string) $review->guestid,
                'guest_name' => $review->guest_name,
                'guest_profile_image' => $profile_image,
                'rating' => (string) $review->guest_rating,
                'message' => $review->guest_message,
                'created_at' => $review->created_at->format('F Y'),
                'updated_at' => $review->updated_at->format('F Y'),
            ];
        }

        $cancelAtionPolicyDescriptions = CancellationPolicy::all()->pluck('description')->toArray();

        switch ($moduleId) {

            case 2:

                return [];

            default:

                return [];
        }
    }

    public function getMakeName($makeId)
    {
        $make = VehicleMake::find($makeId);

        return $make ? $make->name : '';
    }

    public function getModelName($modeld)
    {
        $model = SubCategory::find($modeld);

        return $model ? $model->name : '';
    }

    public function getodometerName($id)
    {
        $odometer = VehicleOdometer::find($id);

        return $odometer ? $odometer->name : '';
    }

    public function getoVehicleType($id)
    {
        $itemType = ItemType::find($id);

        return $itemType ? $itemType->name : '';
    }

    public function generateUniqueToken()
    {
        $timestamp = time();
        $randomChars = $this->generateRandomChars(60);
        $token = $timestamp.$randomChars;

        // Check uniqueness across all tables, regenerate if necessary
        while ($this->isTokenExistsInAnyTable($token)) {
            $timestamp = time();
            $randomChars = $this->generateRandomChars(60);
            $token = $timestamp.$randomChars;
        }
        $token = str_shuffle($token);

        return $token;
    }

    public function generateRandomChars($length)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomChars = '';

        for ($i = 0; $i < $length; $i++) {
            $randomChars .= $characters[random_int(0, $charactersLength - 1)];
        }

        return str_shuffle($randomChars);
    }

    public function isTokenExistsInAnyTable($token)
    {
        // Replace this with your logic to check if the token exists in any table
        // You might need to execute a query for each table or use another approach based on your database structure
        // Return true if the token exists in any table, false otherwise
        return false;
    }

    public function getTimeOptions()
    {
        $hours = [];

        for ($hour = 1; $hour <= 1; $hour++) {
            $minutes = $hour * 60;
            $hours["$hour hour"] = "{$minutes}";
        }

        return $hours;
    }

    public function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Earth's radius in kilometers
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = number_format(($earthRadius * $c), 2);

        return $distance;
    }

    public function storeUserMeta($userId, $metaKey, $metaValue)
    {
        // Find existing meta or create a new one
        $meta = AppUserMeta::updateOrCreate(
            ['user_id' => $userId, 'meta_key' => $metaKey],
            ['meta_value' => $metaValue]
        );
    }

    public function formatPriceWithConversion($price, $currencyCode, $conversionRate, $locale = 'en_US', $calender = 0)
    {
        $convertedPrice = $price * $conversionRate;
        $formattedPrice = round($convertedPrice);

        return (string) $formattedPrice;
    }

    public function checkRemainingItems($userId, $module)
    {

        $itemCount = Item::where('userid_id', $userId)
            ->where('module', $module)
            ->count();

        if ($itemCount >= 1) {
            return null;
        }

        return 1 - $itemCount;
    }

    public function checkTotalNoOfBookingPerDay($userId)
    {
        $currentDate = date('Y-m-d');

        $bookingCount = Booking::where('userid', $userId)
            ->whereDate('created_at', $currentDate)
            ->count();

        $totalBookingsPerDaySetting = GeneralSetting::where('meta_key', 'total_number_of_bookings_per_day')
            ->pluck('meta_value')
            ->first();

        if ($bookingCount >= $totalBookingsPerDaySetting) {

            return null;
        }

        return $bookingCount;
    }

    public function createFirebaseUser($email, $password, $apiKey)
    {
        $url = 'https://identitytoolkit.googleapis.com/v1/accounts:signUp?key='.$apiKey;
        $data = json_encode([
            'email' => $email,
            'password' => $password,
            'returnSecureToken' => true,
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200) {
            // Decode the successful response
            $result = json_decode($response, true);

            return [
                'success' => true,
                'uid' => $result['localId'],
                'idToken' => $result['idToken'],
                'email' => $result['email'],
            ];
        } else {
            // Decode the error response
            $error = json_decode($response, true);
            $errorMessage = isset($error['error']['message']) ? $error['error']['message'] : 'Unknown error occurred';

            if (strpos($errorMessage, 'EMAIL_EXISTS') !== false) {

                return [
                    'success' => true,
                ];
            }

            return [
                'success' => false,
                'message' => $errorMessage,
            ];
        }
    }
}
