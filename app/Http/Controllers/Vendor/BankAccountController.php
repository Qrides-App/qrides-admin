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
use App\Models\AppUsersBankAccount;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    use EmailTrait, MediaUploadingTrait, NotificationTrait, PushNotificationTrait, ResponseTrait, SMSTrait, UserWalletTrait, VendorWalletTrait;

    public function index()
    {
        if (auth()->check()) {
            $user = auth()->user();
            $vendorId = $user->id;
        }

        $bankAccount = AppUsersBankAccount::where('user_id', $vendorId)->first();

        return view('vendor.bankAccount.index', compact('bankAccount'));

    }

    public function storeOrUpdate(Request $request)
    {
        $request->validate([
            'account_name' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'branch_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'iban' => 'required|string|max:255',
            'swift_code' => 'required|string|max:255',
        ]);

        $bankAccount = AppUsersBankAccount::firstOrNew(['user_id' => auth()->user()->id]);

        $bankAccount->fill($request->only([
            'account_name', 'bank_name', 'branch_name',
            'account_number', 'iban', 'swift_code',
        ]));

        $bankAccount->save();

        return redirect()->route('vendor.bankaccount')->with('success', 'Bank account information saved successfully.');
    }
}
