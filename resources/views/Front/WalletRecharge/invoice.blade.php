<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recharge Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 24px; background: #f5f7fb; color: #111827; }
        .invoice { max-width: 880px; margin: 0 auto; background: #fff; padding: 32px; border-radius: 16px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08); }
        .header { display: flex; justify-content: space-between; gap: 24px; flex-wrap: wrap; margin-bottom: 24px; }
        .muted { color: #6b7280; }
        .title { margin: 0 0 8px; font-size: 28px; }
        .box { background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 24px; }
        th, td { padding: 12px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        th { background: #f8fafc; }
        .totals { margin-top: 24px; width: 320px; margin-left: auto; }
        .totals-row { display: flex; justify-content: space-between; padding: 8px 0; }
        .grand { font-weight: 700; font-size: 18px; border-top: 1px solid #d1d5db; padding-top: 12px; }
    </style>
</head>
<body>
    <div class="invoice">
        <div class="header">
            <div>
                <h1 class="title">Tax Invoice</h1>
                <div><strong>{{ $branding['name'] ?? config('app.name') }}</strong></div>
                @if (!empty($branding['email']))
                    <div class="muted">{{ $branding['email'] }}</div>
                @endif
                @if (!empty(trim((string) ($branding['phone'] ?? ''))))
                    <div class="muted">{{ $branding['phone'] }}</div>
                @endif
                @if (!empty($branding['gstin']))
                    <div class="muted">GSTIN: {{ $branding['gstin'] }}</div>
                @endif
            </div>
            <div class="box">
                <div><strong>Invoice No:</strong> {{ $invoice->invoice_number }}</div>
                <div><strong>Invoice Date:</strong> {{ optional($invoice->invoice_date)->format('d M Y h:i A') }}</div>
                <div><strong>Payment Method:</strong> {{ strtoupper((string) $invoice->payment_method) }}</div>
                <div><strong>Payment Status:</strong> {{ strtoupper((string) $invoice->payment_status) }}</div>
                @if ($invoice->transaction_id)
                    <div><strong>Transaction ID:</strong> {{ $invoice->transaction_id }}</div>
                @endif
            </div>
        </div>

        <div class="header">
            <div class="box" style="flex:1;">
                <strong>Billed To</strong>
                <div>{{ trim(($invoice->driver->first_name ?? '').' '.($invoice->driver->last_name ?? '')) ?: 'Captain' }}</div>
                @if (!empty($invoice->driver->email))
                    <div class="muted">{{ $invoice->driver->email }}</div>
                @endif
                @if (!empty($invoice->driver->phone))
                    <div class="muted">{{ ($invoice->driver->phone_country ?? '') . $invoice->driver->phone }}</div>
                @endif
            </div>
            <div class="box" style="flex:1;">
                <strong>Recharge Details</strong>
                <div>{{ $invoice->plan->name ?? ('Driver recharge for '.$invoice->duration_days.' day(s)') }}</div>
                <div class="muted">Duration: {{ $invoice->duration_days }} day(s)</div>
                <div class="muted">Currency: {{ $invoice->currency_code }}</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Taxable Value</th>
                    <th>GST Rate</th>
                    <th>GST Amount</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Captain recharge access plan</td>
                    <td>{{ number_format((float) $invoice->taxable_amount, 2) }}</td>
                    <td>{{ number_format((float) $invoice->gst_rate, 2) }}%</td>
                    <td>{{ number_format((float) $invoice->gst_amount, 2) }}</td>
                    <td>{{ number_format((float) $invoice->total_amount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="totals">
            <div class="totals-row">
                <span>Taxable Amount</span>
                <span>{{ number_format((float) $invoice->taxable_amount, 2) }}</span>
            </div>
            <div class="totals-row">
                <span>GST</span>
                <span>{{ number_format((float) $invoice->gst_amount, 2) }}</span>
            </div>
            <div class="totals-row grand">
                <span>Total Paid</span>
                <span>{{ $invoice->currency_code }} {{ number_format((float) $invoice->total_amount, 2) }}</span>
            </div>
        </div>
    </div>
</body>
</html>
