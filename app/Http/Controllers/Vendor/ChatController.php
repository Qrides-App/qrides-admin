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
        $firebaseConfig = array_filter(config('services.firebase_web', []), static function ($value) {
            return $value !== null && $value !== '';
        });
        $firebaseProjectId = $this->getFirebaseProjectId();
        $firebaseProjectMismatch = ! empty($firebaseProjectId)
            && ! empty($firebaseConfig['projectId'] ?? null)
            && $firebaseProjectId !== ($firebaseConfig['projectId'] ?? null);

        return view('vendor.chat.chat', compact('firebaseConfig', 'firebaseProjectMismatch', 'firebaseProjectId'));
    }
}
