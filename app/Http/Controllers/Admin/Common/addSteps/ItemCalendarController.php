<?php

namespace App\Http\Controllers\Admin\Common\addSteps;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\BookingAvailableTrait;
use App\Http\Controllers\Traits\CommonModuleItemTrait;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Models\GeneralSetting;
use App\Models\Modern\Item;
use App\Models\Modern\ItemDate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class ItemCalendarController extends Controller
{
    use BookingAvailableTrait,CommonModuleItemTrait,MediaUploadingTrait;

    public function calendar(Request $request, $id)
    {
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
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
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

        $leftSideMenu = $this->getLeftSideMenu($module);

        $routeIndex = 'admin.'.$slug.'.calendar.index';
        $routeUpdate = 'admin.'.$slug.'.Calendar-Update';

        return view('admin.common.addSteps.calendar.calendar', compact('month', 'year', 'numDays', 'monthNames', 'dayNames', 'firstDayOfWeek', 'id', 'prevMonth', 'prevYear', 'nextMonth', 'nextYear', 'priceData', 'general_default_currency', 'module', 'leftSideMenu', 'routeIndex', 'routeUpdate', 'itemData'));

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
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
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
        $routeIndex = 'admin.'.$slug.'.calendar.index';
        $routeUpdate = 'admin.'.$slug.'.Calendar-Update';
        $calendarHtml = view('admin.common.addSteps.calendar.calendar_table', compact('month', 'year', 'numDays', 'monthNames', 'dayNames', 'firstDayOfWeek', 'id', 'prevMonth', 'prevYear', 'nextMonth', 'nextYear', 'priceData', 'dateData', 'StatusData', 'general_default_currency', 'bookingData', 'minStayData', 'prices', 'module', 'routeIndex', 'routeUpdate', 'itemData'))->render();
        $updatedCalendarData = [
            'html' => $calendarHtml,
        ];

        return response()->json($updatedCalendarData);
    }

    public function CalandarUpdate(Request $request)
    {
        $this->commonCalanderUpdate($request);
    }
}
