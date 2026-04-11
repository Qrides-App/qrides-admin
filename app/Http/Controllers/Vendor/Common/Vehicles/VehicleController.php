<?php

namespace App\Http\Controllers\Vendor\Common\Vehicles;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\BookingAvailableTrait;
use App\Http\Controllers\Traits\CommonModuleItemTrait;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Controllers\Traits\MiscellaneousTrait;
use App\Http\Controllers\Traits\Vendor\VendorMiscellaneousTrait;
use App\Models\AppUser;
use App\Models\CancellationPolicy;
use App\Models\Category;
use App\Models\City;
use App\Models\GeneralSetting;
use App\Models\Modern\Item;
use App\Models\Modern\ItemDate;
use App\Models\Modern\ItemFeatures;
use App\Models\Modern\ItemMeta;
use App\Models\Modern\ItemType;
use App\Models\Modern\ItemVehicle;
use App\Models\SubCategory;
use App\Models\VehicleOdometer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class VehicleController extends Controller
{
    use BookingAvailableTrait, CommonModuleItemTrait, MediaUploadingTrait, MiscellaneousTrait, VendorMiscellaneousTrait;

    public function base(Request $request, $id)
    {
        $this->vendorItemAuthentication($id);
        $itemVehicle = ItemVehicle::where('item_id', $id)->first();
        $YoulistingData = '';

        $YearData = $itemVehicle->year ?? null;
        $TransmissionData = $itemVehicle->transmission ?? null;
        $OdometerData = $itemVehicle->odometer ?? null;

        $vehicleTypeData = ItemType::where('module', 2)->get();
        $vehicleData = Item::where('id', $id)->first();
        $vehicleMakeData = Category::where('module', 2)->get();
        $MakeData = $vehicleData->category_id;
        $ModelData = $vehicleData->subcategory_id;

        $vehicleOdoMeterData = VehicleOdometer::all();
        if ($YoulistingData) {
        } else {
            $YoulistingData = '';
        }

        return view('vendor.vehicles.base', compact('id', 'vehicleMakeData', 'vehicleData', 'vehicleOdoMeterData', 'YoulistingData', 'MakeData', 'ModelData', 'YearData', 'TransmissionData', 'OdometerData', 'vehicleTypeData'));
    }

    public function baseUpdate(Request $request)
    {

        $request->validate([
            'car_type' => 'required|numeric',
            'make' => 'required|numeric',
            'model' => 'required|numeric',
            'year' => 'required|numeric',
        ]);

        $id = $request->input('id');
        $this->vendorItemAuthentication($id);
        $car_type = $request->input('car_type');
        $itemData = Item::findOrFail($id);
        $itemData->update([
            'item_type_id' => $car_type,
            'category_id' => $request->input('make'),
            'subcategory_id' => $request->input('model'),
        ]);

        $data = [
            'year' => $request->input('year'),
            'transmission' => $request->input('transmission'),
            'odometer' => $request->input('odometer'),
        ];

        $identifier = [
            'item_id' => $id,
        ];

        $itemVehicle = ItemVehicle::updateOrCreate($identifier, $data);

        $itemMetaInfo = $this->getModuleInfoValues($itemData->module, $itemData->id);
        $data = [
            'itemMetaInfo' => $itemMetaInfo ?? null,
        ];
        $this->addOrUpdateItemMeta($itemData->id, $data);

        $this->updateStepCompleted($itemData->id, 'basic', true);
    }

    public function description(Request $request, $id)
    {
        $this->vendorItemAuthentication($id);
        $realRoute = explode('.', Route::currentRouteName())[1] ?? null;
        $module = $this->getTheModule($realRoute);
        $permissionrealRoute = str_replace('-', '_', $realRoute);
        $slug = $this->getTheModuleTitle($realRoute);

        $itemData = Item::with('appUser')->where('id', $id)->first();
        $userids = AppUser::pluck('first_name', 'id')->prepend(trans('global.pleaseSelect'), '');
        $backButtonRoute = 'vendor.'.$realRoute.'.description';
        $updateLocationRoute = '';
        $nextButton = '/vendor/'.$realRoute.'/location/';
        $leftSideMenu = $this->getLeftSideMenuVendor($module);

        return view('vendor.vehicles.description', compact('id', 'itemData', 'permissionrealRoute', 'userids', 'backButtonRoute', 'updateLocationRoute', 'nextButton', 'leftSideMenu'));
    }

    public function updateTitleDescription(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric',
            'name' => 'required|string',
            'summary' => 'required|string',
            'style_note' => 'nullable|string',
            'chart_image' => 'nullable|string',

        ]);

        $id = $request->id;
        $this->vendorItemAuthentication($id);
        $itemData = Item::findOrFail($id);

        $itemData->update([
            'title' => $request->name,
            'description' => $request->summary,
            'userid_id' => $request->userid_id,
        ]);
        $itemMetaInfo = $this->getModuleInfoValues('', $id);

        $data = [
            'itemMetaInfo' => $itemMetaInfo ?? null,
        ];
        $this->addOrUpdateItemMeta($id, $data);

        // If style_note is provided, save it to Item Meta
        if ($request->has('style_note')) {
            ItemMeta::updateOrCreate(
                ['rental_item_id' => $id, 'meta_key' => 'style_note'],
                ['meta_value' => $request->style_note]
            );
        }

        // If chart_image is provided, save it to ItemMeta
        if ($request->has('chart_image')) {
            ItemMeta::updateOrCreate(
                ['item_id' => $id, 'meta_key' => 'chart_image'],
                ['meta_value' => $request->chart_image]
            );
        }
        $this->updateStepCompleted($id, 'title', true);

        return response()->json(['success' => true]);
    }

    public function location(Request $request, $id)
    {
        $this->vendorItemAuthentication($id);
        $itemData = Item::where('id', $id)->first();
        $cityData = City::where('module', $itemData->module)->where('status', '1')->get();
        $api_google_map_key = GeneralSetting::where('meta_key', 'api_google_map_key')->first();

        $realRoute = explode('.', Route::currentRouteName())[1] ?? null;
        $module = $this->getTheModule($realRoute);
        $permissionrealRoute = str_replace('-', '_', $realRoute);
        $slug = $this->getTheModuleTitle($realRoute);

        $backButtonRoute = 'vendor.'.$realRoute.'.description';
        $updateLocationRoute = 'vendor.location-Update';
        $nextButton = '/vendor/'.$realRoute.'/features/';
        $leftSideMenu = $this->getLeftSideMenuVendor($module);

        return view('vendor.vehicles.addSteps.location', compact('id', 'api_google_map_key', 'itemData', 'cityData', 'backButtonRoute', 'updateLocationRoute', 'nextButton', 'leftSideMenu'));
    }

    public function locationUpdate(Request $request)
    {

        $this->commonnlocationUpdate($request);
    }

    public function features(Request $request, $id)
    {
        $this->vendorItemAuthentication($id);
        $itemData = Item::where('id', $id)->first();
        $features_ids = explode(',', $itemData->features_id);

        $features = ItemFeatures::where('module', $itemData->module)
            ->where(function ($query) {
                $query->whereNull('type')
                    ->orWhere('type', '');
            })
            ->get();

        $realRoute = explode('.', Route::currentRouteName())[1] ?? null;

        $module = $this->getTheModule($realRoute);
        $permissionrealRoute = str_replace('-', '_', $realRoute);
        $slug = $this->getTheModuleTitle($realRoute);

        $backButtonRoute = 'vendor.'.$realRoute.'.location';
        $updateLocationFeature = 'vendor.features-Update';
        $nextButton = '/vendor/'.$realRoute.'/photos/';
        $leftSideMenu = $this->getLeftSideMenuVendor($module);

        return view('vendor.vehicles.addSteps.features', compact('id', 'itemData', 'features', 'features_ids', 'backButtonRoute', 'updateLocationFeature', 'nextButton', 'leftSideMenu'));
    }

    public function featuresUpdate(Request $request)
    {

        $this->CommonFeaturesUpdate($request);
    }

    public function photos(Request $request, $id)
    {
        $this->vendorItemAuthentication($id);
        $itemData = Item::where('id', $id)->first();

        $realRoute = explode('.', Route::currentRouteName())[1] ?? null;
        $module = $this->getTheModule($realRoute);
        $permissionrealRoute = str_replace('-', '_', $realRoute);
        $slug = $this->getTheModuleTitle($realRoute);

        $itemData = Item::findOrFail($id);
        $backButtonRoute = 'vendor.'.$realRoute.'.features';
        $updatePhoto = 'vendor.photos-Update';
        $nextButton = '/vendor/'.$realRoute.'/pricing/';
        $storeMedia = 'vendor.storeMedia';
        $leftSideMenu = $this->getLeftSideMenuVendor($module);

        return view('vendor.vehicles.addSteps.photos', compact('id', 'itemData', 'backButtonRoute', 'updatePhoto', 'nextButton', 'storeMedia', 'leftSideMenu'));
    }

    public function photosUpdate(Request $request)
    {
        $this->CommonPhotosUpdate($request);
    }

    public function pricing(Request $request, $id)
    {
        $this->vendorItemAuthentication($id);
        $item = Item::findOrFail($id);

        $itemMetas = $item->itemMeta->pluck('meta_value', 'meta_key');

        $night_price = $item->price;
        $serviceType = $item->service_type;

        $weeklyDiscount = $itemMetas->get('weekly_discount', 0);
        $monthlyDiscount = $itemMetas->get('monthly_discount', 0);
        $weeklyDiscountType = $itemMetas->get('weekly_discount_type', 'percent');
        $monthlyDiscountType = $itemMetas->get('monthly_discount_type', 'percent');
        $leftSideMenu = $this->getLeftSideMenuVendor(2);
        $updatePricing = 'vendor.vehicles.prices-Update';

        $doorStep = ItemMeta::where('rental_item_id', $id)->where('meta_key', 'doorStep_price')->first();
        $securityFee = ItemMeta::where('rental_item_id', $id)->where('meta_key', 'security_fee')->first();

        $general_default_currency = GeneralSetting::where('meta_key', 'general_default_currency')->first();

        return view('vendor.vehicles.addSteps.pricing', compact('id', 'leftSideMenu', 'updatePricing', 'general_default_currency', 'weeklyDiscount', 'monthlyDiscount', 'serviceType', 'doorStep', 'night_price', 'securityFee', 'item', 'weeklyDiscountType', 'monthlyDiscountType'));
    }

    public function pricesUpdate(Request $request)
    {
        $request->validate([
            'night_price' => 'required|numeric|gt:0',
            'service_type' => 'required|string',
        ]);
        $id = $request->input('id');
        $this->vendorItemAuthentication($id);
        $itemData = Item::findOrFail($id);
        $weeklydiscountType = $request->input('weekly_discount_type') ?: 'percent';
        $monthlyDiscountType = $request->input('monthly_discount_type') ?: 'percent';

        $itemData->update([
            'price' => $request->input('night_price'),
            'service_type' => $request->input('service_type'),

        ]);
        $data = [
            'doorStep_price' => $request->input('doorstep_delivery_price'),
            'security_fee' => $request->input('security_fee'),
            'weekly_discount' => $request->input('weekly_discount'),
            'monthly_discount' => $request->input('monthly_discount'),
            'monthly_discount_type' => $monthlyDiscountType,
            'weekly_discount_type' => $weeklydiscountType,
        ];

        $this->addOrUpdateItemMeta($id, $data);
        $itemMetaInfo = $this->getModuleInfoValues($itemData->module, $itemData->id);
        $data = [
            'itemMetaInfo' => $itemMetaInfo ?? null,
        ];
        $this->addOrUpdateItemMeta($itemData->id, $data);
        $this->updateStepCompleted($id, 'price', true);
    }

    public function CancellationPolicies(Request $request, $id)
    {

        $this->vendorItemAuthentication($id);
        $realRoute = explode('.', Route::currentRouteName())[1] ?? null;
        $module = $this->getTheModule($realRoute);
        $cancellationPolicyData = CancellationPolicy::where('module', $module)->get();
        $rules = ItemMeta::where('rental_item_id', $id)->where('meta_key', 'rules')->first();
        $policy = Item::where('id', $id)->first();
        $serviceType = $policy->service_type;

        $permissionrealRoute = str_replace('-', '_', $realRoute);
        $slug = $this->getTheModuleTitle($realRoute);

        $itemRules = $this->getItemRule($policy->module);
        $backButtonRoute = 'vendor.'.$realRoute.'.pricing';
        $updatePolicyRoute = 'vendor.cancellation-policies-Update';
        $nextButton = '/vendor/'.$realRoute.'/calendar/';
        $leftSideMenu = $this->getLeftSideMenuVendor($module);

        return view('vendor.vehicles.addSteps.cancellationPolicies', compact('id', 'cancellationPolicyData', 'serviceType', 'rules', 'policy', 'itemRules', 'backButtonRoute', 'updatePolicyRoute', 'nextButton', 'leftSideMenu'));
    }

    public function cancellationPoliciesUpdate(Request $request)
    {
        $this->CommonCancellationPoliciesUpdate($request);
    }

    public function calendar(Request $request, $id)
    {
        $this->vendorItemAuthentication($id);
        $realRoute = explode('.', Route::currentRouteName())[1] ?? null;
        $module = $this->getTheModule($realRoute);
        $permissionrealRoute = str_replace('-', '_', $realRoute);
        $slug = $this->getTheModuleTitle($realRoute);

        $month = str_pad($request->input('month', date('n')), 2, '0', STR_PAD_LEFT);
        $year = $request->input('year', date('Y'));

        // Calculate the previous month and year
        $prevMonth = $month - 1;
        $prevYear = $year;
        if ($prevMonth < 1) {
            $prevMonth = 12;
            $prevYear--;
        }

        // Calculate the next month and year
        $nextMonth = $month + 1;
        $nextYear = $year;
        if ($nextMonth > 12) {
            $nextMonth = 1;
            $nextYear++;
        }
        $itemData = Item::findOrFail($id);

        $numDays = date('t', strtotime("$year-$month-01"));

        $monthNames = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];

        $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

        $firstDayOfWeek = date('w', strtotime("$year-$month-01"));

        $prices = ItemDate::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->where('item_id', $id)
            ->get();

        $priceData = [];
        foreach ($prices as $price) {
            $date = Carbon::createFromFormat('Y-m-d', $price->date);
            $priceData[$date->format('Y-m-d')] = $price->price;
        }

        $general_default_currency = GeneralSetting::where('meta_key', 'general_default_currency')->first();

        $leftSideMenu = $this->getLeftSideMenuVendor($module);

        $routeIndex = 'vendor.'.$slug.'.calendar.index';
        $routeUpdate = 'vendor.'.$slug.'.Calendar-Update';

        return view('vendor.vehicles.addSteps.calendar', compact('month', 'year', 'numDays', 'monthNames', 'dayNames', 'firstDayOfWeek', 'id', 'prevMonth', 'prevYear', 'nextMonth', 'nextYear', 'priceData', 'general_default_currency', 'module', 'leftSideMenu', 'routeIndex', 'routeUpdate', 'itemData'));
    }

    public function calendarMonth(Request $request, $id)
    {
        $realRoute = explode('.', Route::currentRouteName())[1] ?? null;
        $module = $this->getTheModule($realRoute);
        $permissionrealRoute = str_replace('-', '_', $realRoute);
        $slug = $this->getTheModuleTitle($realRoute);

        $month = str_pad($request->input('month', date('n')), 2, '0', STR_PAD_LEFT);
        $year = $request->input('year', date('Y'));
        $module = $request->input('module', '1');

        $prevMonth = $month - 1;
        $prevYear = $year;
        if ($prevMonth < 1) {
            $prevMonth = 12;
            $prevYear--;
        }
        $itemData = Item::findOrFail($id);

        $nextMonth = $month + 1;
        $nextYear = $year;
        if ($nextMonth > 12) {
            $nextMonth = 1;
            $nextYear++;
        }

        $numDays = date('t', strtotime("$year-$month-01"));

        $monthNames = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];

        $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

        $firstDayOfWeek = date('w', strtotime("$year-$month-01"));

        $prices = ItemDate::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->where('item_id', $id)
            ->get();

        $priceData = $dateData = $StatusData = $bookingData = $minStayData = [];
        foreach ($prices as $price) {
            $date = Carbon::createFromFormat('Y-m-d', $price->date);
            $priceData[$date->format('Y-m-d')] = $price->price;
            $dateData[$date->format('Y-m-d')] = $price->date;
            $StatusData[$date->format('Y-m-d')] = $price->status;
            $bookingData[$date->format('Y-m-d')] = $price->booking_id;
            $minStayData[$date->format('Y-m-d')] = $price->min_stay;
        }
        $general_default_currency = GeneralSetting::where('meta_key', 'general_default_currency')->first();
        $routeIndex = 'vendor.'.$slug.'.calendar.index';
        $routeUpdate = 'vendor.'.$slug.'.Calendar-Update';
        $calendarHtml = view('vendor.vehicles.addSteps.calendar_table', compact('month', 'year', 'numDays', 'monthNames', 'dayNames', 'firstDayOfWeek', 'id', 'prevMonth', 'prevYear', 'nextMonth', 'nextYear', 'priceData', 'dateData', 'StatusData', 'general_default_currency', 'bookingData', 'minStayData', 'prices', 'module', 'routeIndex', 'routeUpdate', 'itemData'))->render();
        $updatedCalendarData = [
            'html' => $calendarHtml,
        ];

        return response()->json($updatedCalendarData);
    }

    public function CalandarUpdate(Request $request)
    {

        $this->commonCalanderUpdate($request);
    }

    public function getVehicleType(Request $request)
    {

        $vehicleModelData = SubCategory::where('make_id', $request->make)->get();

        return response()->json($vehicleModelData);

    }

    public function getVehicleMake(Request $request)
    {
        $typeId = $request->input('typeId');
        $query = Category::where('module', 2);

        if ($typeId) {
            $query->whereHas('categoryTypeRelations', function ($q) use ($typeId) {
                $q->where('type_id', $typeId);
            });
        }

        $vehicleMakeDataAll = $query->get();

        return response()->json($vehicleMakeDataAll);

    }
}
