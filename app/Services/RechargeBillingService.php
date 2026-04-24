<?php

namespace App\Services;

use App\Models\AppUser;
use App\Models\DriverRechargeInvoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class RechargeBillingService
{
    public function createInvoice(AppUser $driver, array $pricing, array $context = []): DriverRechargeInvoice
    {
        return DriverRechargeInvoice::create([
            'invoice_number' => $this->generateInvoiceNumber(),
            'public_token' => Str::random(48),
            'driver_id' => $driver->id,
            'driver_recharge_plan_id' => $context['plan_id'] ?? null,
            'payment_method' => $context['payment_method'] ?? null,
            'payment_status' => $context['payment_status'] ?? 'completed',
            'transaction_id' => $context['transaction_id'] ?? null,
            'currency_code' => strtoupper((string) ($pricing['currency_code'] ?? 'INR')),
            'duration_days' => (int) ($pricing['duration_days'] ?? 0),
            'taxable_amount' => round((float) ($pricing['base_amount'] ?? 0), 2),
            'gst_rate' => round((float) ($pricing['gst_percentage'] ?? 0), 2),
            'gst_amount' => round((float) ($pricing['gst_amount'] ?? 0), 2),
            'total_amount' => round((float) ($pricing['amount'] ?? 0), 2),
            'invoice_date' => Carbon::now(),
            'metadata' => $context['metadata'] ?? null,
        ]);
    }

    public function toPayload(DriverRechargeInvoice $invoice): array
    {
        return [
            'invoice_number' => $invoice->invoice_number,
            'invoice_date' => optional($invoice->invoice_date)->toDateTimeString(),
            'payment_method' => $invoice->payment_method,
            'payment_status' => $invoice->payment_status,
            'transaction_id' => $invoice->transaction_id,
            'duration_days' => $invoice->duration_days,
            'taxable_amount' => round((float) $invoice->taxable_amount, 2),
            'gst_rate' => round((float) $invoice->gst_rate, 2),
            'gst_amount' => round((float) $invoice->gst_amount, 2),
            'total_amount' => round((float) $invoice->total_amount, 2),
            'currency_code' => $invoice->currency_code,
            'invoice_url' => route('recharge_invoice.show', ['token' => $invoice->public_token]),
        ];
    }

    private function generateInvoiceNumber(): string
    {
        do {
            $invoiceNumber = 'RCH-'.now()->format('Ymd').'-'.strtoupper(Str::random(8));
        } while (DriverRechargeInvoice::where('invoice_number', $invoiceNumber)->exists());

        return $invoiceNumber;
    }
}
