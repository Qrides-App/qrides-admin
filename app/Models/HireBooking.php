<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\AppUser;
use App\Models\Modern\Item;

class HireBooking extends Model
{
    protected $table = 'hire_bookings';

    protected $fillable = [
        'client_request_id',
        'user_id',
        'driver_id',
        'item_id',
        'duration_hours',
        'start_at',
        'end_at',
        'amount_to_pay',
        'currency_code',
        'payment_method',
        'payment_status',
        'status',
    ];

    protected $casts = [
        'duration_hours' => 'integer',
        'amount_to_pay' => 'float',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function driver()
    {
        return $this->belongsTo(AppUser::class, 'driver_id');
    }

    public function rider()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
