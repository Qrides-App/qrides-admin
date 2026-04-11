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
use App\Models\Booking;
use App\Models\GeneralSetting;
use App\Models\Modern\Item;
use App\Models\VendorWallet;

class VendorHomeController extends Controller
{
    use EmailTrait, MediaUploadingTrait, NotificationTrait, PushNotificationTrait, ResponseTrait, SMSTrait, UserWalletTrait, VendorWalletTrait;

    public function dashboard()
    {

        if (auth()->check()) {
            $user = auth()->user();
            $vendorId = $user->id;
        }
        $from = request()->input('from');
        $to = request()->input('to');
        $status = request()->input('status');

        $vendor_wallets = VendorWallet::with('appUser')
            ->where('vendor_id', $vendorId)
            ->orderBy('id', 'desc')
            ->paginate(50);

        $general_default_currency = GeneralSetting::where('meta_key', 'general_default_currency')->first();

        $hostspendmoney = number_format($this->getVendorWalletBalance($vendorId), 2);
        $hostpendingmoney = number_format($this->getTotalWithdrawlForVendor($vendorId, 'Pending'), 2);
        $hostrecivemoney = number_format($this->getTotalWithdrawlForVendor($vendorId, 'Success'), 2);

        $totalmoney = number_format($this->getTotalEarningsForVendor($vendorId), 2);
        $refunded = number_format($this->getTotalRefundForVendor($vendorId, ''), 2);
        $incoming_amount = number_format($this->getTotalIncomeForVendor($vendorId, ''), 2);
        $module = 2;
        $totalSales = Booking::where('host_id', $vendorId)
            ->where('module', $module)
            ->where('payment_status', 'Paid')
            ->where('status', 'Completed')
            ->count();

        $todayOrders = Booking::where('host_id', $vendorId)
            ->where('module', $module)
            ->where('payment_status', 'Paid')
            ->whereDate('created_at', today())
            ->count();

        $allProducts = Item::where('userid_id', $vendorId)
            ->where('module', $module)
            ->count();

        $pendingOrders = Booking::where('host_id', $vendorId)
            ->where('module', $module)
            ->where('payment_status', 'Paid')
            ->where('status', 'Pending')
            ->count();

        return view('vendor.index', compact('vendorId', 'hostspendmoney', 'hostpendingmoney', 'hostrecivemoney', 'totalmoney', 'refunded', 'incoming_amount', 'vendor_wallets', 'totalSales', 'todayOrders', 'allProducts', 'pendingOrders', 'general_default_currency'));

    }
}
