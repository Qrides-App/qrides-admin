<?php

namespace App\Http\Controllers\Front\WalletRecharge;

use App\Http\Controllers\Controller;
use App\Models\DriverRechargeInvoice;
use App\Models\GeneralSetting;

class RechargeInvoiceController extends Controller
{
    public function show(string $token)
    {
        $invoice = DriverRechargeInvoice::with(['driver', 'plan'])
            ->where('public_token', $token)
            ->firstOrFail();

        $settings = GeneralSetting::whereIn('meta_key', [
            'general_name',
            'general_email',
            'general_phone',
            'general_default_phone_country',
            'general_gstin',
        ])->get()->pluck('meta_value', 'meta_key');

        return view('Front.WalletRecharge.invoice', [
            'invoice' => $invoice,
            'branding' => [
                'name' => $settings->get('general_name', config('app.name')),
                'email' => $settings->get('general_email'),
                'phone' => trim((string) $settings->get('general_default_phone_country', '').' '.(string) $settings->get('general_phone', '')),
                'gstin' => $settings->get('general_gstin'),
            ],
        ]);
    }
}
