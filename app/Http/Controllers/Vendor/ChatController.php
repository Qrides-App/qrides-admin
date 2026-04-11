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
class ChatController extends Controller
{
    use EmailTrait, MediaUploadingTrait, NotificationTrait, PushNotificationTrait, ResponseTrait, SMSTrait, UserWalletTrait, VendorWalletTrait;

    public function chatPage()
    {
        if (auth()->check()) {
            $user = auth()->user();
            $vendorId = $user->id;
        }
        return view('vendor.chat.chat');

    }
}
