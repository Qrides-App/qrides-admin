<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingExtension extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'is_item_deliver',
        'is_item_received',
        'is_item_returned',
        'doorStep_price',
        'pickup_location',
        'dropoff_location',
        'estimated_distance_km',
        'estimated_duration_min',
        'pick_otp',
        'ride_id',
        'captain_payment_mode',
        'captain_payment_reference',
        'payment_collection_note',
        'app_payment_request_token',
        'app_payment_request_url',
        'app_payment_request_expires_at',
        'payment_collected_at',
        'share_token',
        'share_tracking_enabled',
        'share_token_expires_at',
    ];

    protected $casts = [
        'pickup_location' => 'array',
        'dropoff_location' => 'array',
        'app_payment_request_expires_at' => 'datetime',
        'payment_collected_at' => 'datetime',
        'share_tracking_enabled' => 'boolean',
        'share_token_expires_at' => 'datetime',
    ];

    // Relationship to the Booking model
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
