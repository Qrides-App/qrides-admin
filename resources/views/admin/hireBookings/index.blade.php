@extends('layouts.admin')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="box-title">QR Hire Bookings</h3>
            <small class="text-muted">Rides created when riders scan a driver's QR</small>
        </div>

        <div class="card-body">
            <form method="get" class="form-inline" style="gap: 8px; flex-wrap: wrap;">
                <input type="text" name="driver_id" class="form-control" style="min-width: 140px;" placeholder="Driver ID"
                    value="{{ $filters['driver_id'] ?? '' }}">
                <input type="text" name="rider_id" class="form-control" style="min-width: 140px;" placeholder="Rider ID"
                    value="{{ $filters['rider_id'] ?? '' }}">
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
                <a href="{{ route('admin.hire-bookings.index') }}" class="btn btn-default">Reset</a>
            </form>

            <div class="row" style="margin-top: 15px;">
                @foreach (['total' => 'Total', 'booked' => 'Booked', 'ongoing' => 'Ongoing', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $key => $label)
                    <div class="col-md-2 col-sm-4 col-xs-6" style="margin-bottom:10px;">
                        <div class="small-box bg-gray">
                            <div class="inner">
                                <h3>{{ $summary[$key] ?? 0 }}</h3>
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
                            <th>Driver</th>
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
                                <td>
                                    @if ($booking->driver)
                                        {{ $booking->driver->first_name }} {{ $booking->driver->last_name }}
                                        <small class="text-muted">(ID {{ $booking->driver->id }})</small>
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
                                <td colspan="9" class="text-center">No hire bookings found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $bookings->links() }}
        </div>
    </div>
@endsection
