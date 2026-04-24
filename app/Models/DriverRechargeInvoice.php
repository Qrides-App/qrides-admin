<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverRechargeInvoice extends Model
{
    protected $table = 'driver_recharge_invoices';

    protected $fillable = [
        'invoice_number',
        'public_token',
        'driver_id',
        'driver_recharge_plan_id',
        'payment_method',
        'payment_status',
        'transaction_id',
        'currency_code',
        'duration_days',
        'taxable_amount',
        'gst_rate',
        'gst_amount',
        'total_amount',
        'invoice_date',
        'metadata',
    ];

    protected $casts = [
        'duration_days' => 'integer',
        'taxable_amount' => 'float',
        'gst_rate' => 'float',
        'gst_amount' => 'float',
        'total_amount' => 'float',
        'invoice_date' => 'datetime',
        'metadata' => 'array',
    ];

    public function driver()
    {
        return $this->belongsTo(AppUser::class, 'driver_id');
    }

    public function plan()
    {
        return $this->belongsTo(DriverRechargePlan::class, 'driver_recharge_plan_id');
    }
}
