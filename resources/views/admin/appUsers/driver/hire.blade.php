@extends('layouts.admin')

@section('content')
    <div class="content container-fluid">
        @include('admin.appUsers.driver.menu')

        <div class="row" style="margin-top: 15px;">
            <div class="col-md-4">
                <div class="box box-solid">
                    <div class="box-header with-border">
                        <h3 class="box-title">Driver QR</h3>
                    </div>
                    <div class="box-body text-center">
                        <p class="text-muted">Riders scan this code to start a hire with this driver.</p>
                        <canvas id="driver_qr_canvas" width="240" height="240" style="margin-bottom:10px;"></canvas>
                        <div><strong>Payload:</strong> <code>{{ $qrPayload }}</code></div>
                        <div class="btn-group" style="margin-top:12px;">
                            <button id="copy_payload" class="btn btn-default btn-sm"><i class="fa fa-copy"></i> Copy Link</button>
                            <button id="download_qr" class="btn btn-primary btn-sm"><i class="fa fa-download"></i> Download</button>
                        </div>
                        <p style="margin-top:10px;" class="text-muted">Driver ID: {{ $driver->id }}</p>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="box box-solid">
                    <div class="box-header with-border d-flex justify-content-between align-items-center">
                        <h3 class="box-title">QR Hire Rides</h3>
                        <small class="text-muted">Currency: {{ $currency }}</small>
                    </div>
                    <div class="box-body">
                        <form method="get" class="form-inline" style="gap: 8px; flex-wrap: wrap;">
                            @php
                                $statuses = [
                                    '' => 'All',
                                    'booked' => 'Booked',
                                    'ongoing' => 'Ongoing',
                                    'completed' => 'Completed',
                                    'cancelled' => 'Cancelled',
                                ];
                            @endphp
                            <select name="status" class="form-control">
                                @foreach ($statuses as $key => $label)
                                    <option value="{{ $key }}" {{ ($filters['status'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <input type="date" name="from" class="form-control" value="{{ $filters['from'] ?? '' }}">
                            <input type="date" name="to" class="form-control" value="{{ $filters['to'] ?? '' }}">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="{{ url('admin/driver/hire/' . $driver->id) }}" class="btn btn-default">Reset</a>
                        </form>

                        <div class="row" style="margin-top: 15px;">
                            @foreach (['total' => 'Total', 'booked' => 'Booked', 'ongoing' => 'Ongoing', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $key => $label)
                                <div class="col-md-4" style="margin-bottom:10px;">
                                    <div class="small-box bg-gray">
                                        <div class="inner">
                                            <h3>{{ $stats[$key] ?? 0 }}</h3>
                                            <p>{{ $label }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Rider</th>
                                        <th>Duration (hrs)</th>
                                        <th>Start</th>
                                        <th>End</th>
                                        <th>Amount</th>
                                        <th>Payment</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($bookings as $booking)
                                        <tr>
                                            <td>#{{ $booking->id }}</td>
                                            <td>
                                                @if ($booking->rider)
                                                    {{ $booking->rider->first_name }} {{ $booking->rider->last_name }}
                                                    <small class="text-muted">(ID {{ $booking->rider->id }})</small>
                                                @else
                                                    <em class="text-muted">N/A</em>
                                                @endif
                                            </td>
                                            <td>{{ $booking->duration_hours }}</td>
                                            <td>{{ optional($booking->start_at)->format('Y-m-d H:i') }}</td>
                                            <td>{{ optional($booking->end_at)->format('Y-m-d H:i') }}</td>
                                            <td>{{ number_format($booking->amount_to_pay, 2, '.', '') }} {{ $booking->currency_code }}</td>
                                            <td>
                                                <div>{{ $booking->payment_method ?? 'cash' }}</div>
                                                <small class="text-muted">{{ $booking->payment_status ?? 'pending' }}</small>
                                            </td>
                                            <td>
                                                @php
                                                    $labelClass = match ($booking->status) {
                                                        'completed' => 'label-success',
                                                        'ongoing' => 'label-primary',
                                                        'cancelled' => 'label-danger',
                                                        default => 'label-default',
                                                    };
                                                @endphp
                                                <span class="label {{ $labelClass }}">{{ ucfirst($booking->status) }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center">No hire rides found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{ $bookings->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
    <script>
        (function() {
            const payload = @json($qrPayload);
            const canvas = document.getElementById('driver_qr_canvas');
            new QRious({
                element: canvas,
                value: payload,
                size: 240,
                background: '#ffffff',
                foreground: '#f1b500'
            });

            document.getElementById('copy_payload').addEventListener('click', function() {
                navigator.clipboard.writeText(payload).then(() => alert('Link copied to clipboard')).catch(() => alert('Could not copy'));
            });

            document.getElementById('download_qr').addEventListener('click', function() {
                const link = document.createElement('a');
                link.download = 'driver-qr-{{ $driver->id }}.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            });
        })();
    </script>
@endsection
