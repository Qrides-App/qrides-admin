<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingAppUserRegistration extends Model
{
    protected $table = 'pending_app_user_registrations';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'phone_country',
        'default_country',
        'user_type',
        'fcm',
        'device_id',
        'token',
        'otp_channel',
        'expires_at',
        'otp_sent_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'otp_sent_at' => 'datetime',
    ];
}
