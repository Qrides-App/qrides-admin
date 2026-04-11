<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\EmailTrait;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Controllers\Traits\NotificationTrait;
use App\Http\Controllers\Traits\PushNotificationTrait;
use App\Http\Controllers\Traits\ResponseTrait;
use App\Http\Controllers\Traits\SMSTrait;
use App\Http\Controllers\Traits\UserWalletTrait;
use App\Http\Controllers\Traits\VendorWalletTrait;
use App\Models\AppUser;
use App\Models\Booking;
use App\Models\GeneralSetting;
use App\Models\Modern\Item;
use App\Models\Module;

class FinanceController extends Controller
{
    use EmailTrait, MediaUploadingTrait, NotificationTrait, PushNotificationTrait, ResponseTrait, SMSTrait, UserWalletTrait, VendorWalletTrait;

    public function index()
    {
        if (auth()->check()) {
            $user = auth()->user();
            $vendorId = $user->id;
        }
        $from = request()->input('from');
        $to = request()->input('to');
        $item = request()->input('item');
        $customer = request()->input('customer');
        $admin = request()->input('admin');
        $status = request()->input('status');
        $currentModule = Module::where('default_module', '1')->first();

        $query = Booking::with(['host:id,first_name,last_name', 'user:id,first_name,last_name'])
            ->where('payment_status', 'paid')
            ->where('host_id', $vendorId)
            ->where('module', $currentModule->id)
            ->whereIn('status', ['Pending', 'Cancelled', 'Confirmed', 'Completed'])
            ->orderBy('id', 'desc');

        // Add date range filter if both 'from' and 'to' are provided
        if ($from && $to) {
            $query->whereBetween('bookings.created_at', [$from.' 00:00:00', $to.' 23:59:59']);
        } elseif ($from) {
            $query->where('bookings.created_at', '>=', $from.' 00:00:00');
        } elseif ($to) {
            $query->where('bookings.created_at', '<=', $to.' 23:59:59');
        }
        if ($status == 'pending') {
            $query->where('bookings.status', 'pending');
        }
        if ($status == 'confirmed') {
            $query->where('bookings.status', 'confirmed');
        }
        if ($status == 'cancelled') {
            $query->where('bookings.status', 'cancelled');
        }
        if ($status == 'completed') {
            $query->where('bookings.status', 'completed');
        }

        if ($customer) {
            $query->where('userid', $customer);
        }
        if ($admin) {
            $query->where('host_id', $admin);
        }
        if ($item) {
            $query->where('itemid', $item);
        }
        // count price and customer
        $filteredBookingsQuery = clone $query;

        $admin_commission = $filteredBookingsQuery->sum('admin_commission');
        $vendor_commission = $filteredBookingsQuery->where('vendor_commission_given', 1)
            ->sum('vendor_commission');

        $bookings = $query->paginate(50);

        $queryParameters = [];
        if ($to != null) {
            $queryParameters['to'] = $to;
        }
        if ($from != null) {
            $queryParameters['from'] = $from;
        }
        if ($status != null) {
            $queryParameters['status'] = $status;
        }
        if ($customer != null) {
            $queryParameters['vendor'] = $customer;
        }
        if ($admin != null) {
            $queryParameters['admin'] = $admin;
        }
        if ($item != null) {
            $queryParameters['item'] = $item;
        }
        if ($from != null && $to != null) {
            $queryParameters['from'] = $from;
            $queryParameters['to'] = $to;
        }

        if ($item != null && $admin != null && $customer != '' && $from != '' && $to != '' && $status != '') {
            $queryParameters['item'] = $item;
            $queryParameters['admin'] = $admin;
            $queryParameters['customer'] = $customer;
            $queryParameters['to'] = $to;
            $queryParameters['from'] = $from;
            $queryParameters['status'] = $status;
        }
        $bookings->appends($queryParameters);

        $currency_code = Booking::first();
        $fielddata = request()->input('customer');
        $fieldname = AppUser::find($fielddata);
        $customersearch = $fieldname ? $fieldname->first_name : 'All';
        $customersearchId = $fieldname ? $fieldname->id : '';

        $fielddata = request()->input('admin');
        $fieldname = AppUser::find($fielddata);
        $adminsearch = $fieldname ? $fieldname->first_name : 'All';
        $adminsearchId = $fieldname ? $fieldname->id : '';

        $fielddataItem = request()->input('item');
        $fieldnameItem = Item::find($fielddataItem);
        $searchfieldItem = $fieldnameItem ? $fieldnameItem->title : 'All';
        $searchfieldItemId = $fieldnameItem ? $fieldnameItem->id : '';

        $general_default_currency = GeneralSetting::where('meta_key', 'general_default_currency')->first();

        return view('vendor.finance.index', compact('bookings', 'admin_commission', 'vendor_commission', 'searchfieldItem', 'searchfieldItemId', 'customersearch', 'customersearchId', 'general_default_currency', 'adminsearch', 'adminsearchId', 'currentModule'));
    }
}
