<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverRechargePlan extends Model
{
    protected $table = 'driver_recharge_plans';

    protected $fillable = [
        'name',
        'duration_days',
        'amount',
        'currency_code',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'duration_days' => 'integer',
        'amount' => 'float',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
}
