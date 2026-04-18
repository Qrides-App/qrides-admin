@extends('layouts.admin')
@section('content')
    @php
        $normalizedStatus = strtolower($bookingData->status ?? '');
        $statusMap = [
            'ongoing' => ['label' => trans('booking.booking_live'), 'class' => 'badge badge-pill label-secondary live-badge'],
            'cancelled' => ['label' => trans('booking.booking_cancelled'), 'class' => 'badge badge-pill badge-danger'],
            'accepted' => ['label' => trans('booking.booking_accepted'), 'class' => 'badge badge-pill badge-success'],
            'approved' => ['label' => trans('booking.booking_approved'), 'class' => 'badge badge-pill badge-success'],
            'rejected' => ['label' => trans('booking.booking_rejected'), 'class' => 'badge badge-pill badge-warning'],
            'completed' => ['label' => trans('booking.booking_completed'), 'class' => 'badge badge-pill badge-info'],
            'refunded' => ['label' => trans('booking.booking_refunded'), 'class' => 'badge badge-pill badge-primary'],
            'confirmed' => ['label' => trans('booking.booking_confirmed'), 'class' => 'badge badge-pill badge-success'],
            'pending' => ['label' => 'Pending', 'class' => 'badge badge-pill badge-warning'],
        ];
        $statusMeta = $statusMap[$normalizedStatus] ?? ['label' => $bookingData->status ?? '-', 'class' => 'badge badge-pill badge-default'];

        $paymentMethod = strtolower($bookingData->payment_method ?? '');
        $paymentBadgeClass = match ($paymentMethod) {
            'cash' => 'badge badge-pill label-secondary',
            'card', 'credit card', 'debit card' => 'badge badge-pill label-primary',
            'paypal' => 'badge badge-pill badge-info',
            'stripe' => 'badge badge-pill label-warning',
            'wallet' => 'badge badge-pill label-success',
            default => 'badge badge-pill label-light',
        };

        $overviewStats = [
            ['label' => 'Ride fare', 'value' => ($bookingData->total ?? '-') . ' ' . ($bookingData->currency_code ?? $general_default_currency->meta_value ?? '')],
            ['label' => 'Driver income', 'value' => ($bookingData->vendor_commission ?? '-') . ' ' . ($bookingData->currency_code ?? $general_default_currency->meta_value ?? '')],
            ['label' => 'Admin commission', 'value' => ($bookingData->admin_commission ?? '-') . ' ' . ($bookingData->currency_code ?? $general_default_currency->meta_value ?? '')],
            ['label' => 'Booked on', 'value' => optional($bookingData->created_at)->format('d M Y')],
        ];

        $bookingInfo = [
            ['label' => trans('booking.booking_date'), 'value' => optional($bookingData->created_at)->format('h:i A, Y-m-d')],
            ['label' => trans('booking.vehicle_number'), 'value' => isset($bookingData->item->registration_number) ? strtoupper($bookingData->item->registration_number) : '-'],
            ['label' => trans('booking.pickup_location'), 'value' => $bookingData->extension && is_array($bookingData->extension->pickup_location) ? $bookingData->extension->pickup_location['address'] : '-'],
            ['label' => trans('booking.destination'), 'value' => $bookingData->extension && is_array($bookingData->extension->dropoff_location) ? $bookingData->extension->dropoff_location['address'] : '-'],
        ];

        $paymentRows = [
            ['label' => trans('booking.base_price'), 'value' => ($bookingData->base_price ?? '-') . ' ' . ($bookingData->currency_code ?? '')],
            ['label' => trans('booking.ride_fare'), 'value' => ($bookingData->total ?? '-') . ' ' . ($bookingData->currency_code ?? '')],
            ['label' => trans('booking.service_charge'), 'value' => ($bookingData->service_charge ?? '-') . ' ' . ($bookingData->currency_code ?? '')],
            ['label' => trans('booking.iva_tax'), 'value' => ($bookingData->iva_tax ?? '-') . ' ' . ($bookingData->currency_code ?? '')],
            ['label' => trans('booking.admin_commission'), 'value' => ($bookingData->admin_commission ?? '-') . ' ' . ($bookingData->currency_code ?? '')],
            ['label' => trans('booking.driver_income'), 'value' => ($bookingData->vendor_commission ?? '-') . ' ' . ($bookingData->currency_code ?? '')],
        ];
    @endphp

    <div class="content admin-screen">
        <div class="admin-screen-header">
            <div>
                <span class="admin-screen-header__eyebrow">Ride detail</span>
                <h1 class="admin-screen-header__title">Booking overview</h1>
                <p class="admin-screen-header__subtitle">Review trip metadata, payments, rider identity, and driver assignment from one focused operational screen.</p>
            </div>
            <div class="admin-screen-header__actions">
                <div class="admin-screen-header__meta">
                    <span>Reservation</span>
                    <strong>{{ $bookingData->token }}</strong>
                </div>
                <a href="{{ route('admin.bookings.index') }}" class="btn btn-default">
                    <i class="fas fa-arrow-left"></i>
                    {{ trans('booking.back_to_bookings') }}
                </a>
            </div>
        </div>

        <div class="admin-surface admin-filter-shell">
            <div class="admin-panel-header">
                <div>
                    <h3>Booking snapshot</h3>
                    <p>Quick operational view of status, identifiers, and money flow.</p>
                </div>
                <div class="admin-chip-row">
                    <span class="badge badge-pill badge-primary live-badge">
                        <i class="fas fa-database table-icon"></i>
                        {{ $bookingData->token }}
                    </span>
                    @if (!empty($bookingData->extension->ride_id))
                        <span class="badge badge-pill badge-success live-badge">
                            <i class="fas fa-fire table-icon"></i>
                            {{ $bookingData->extension->ride_id }}
                        </span>
                    @endif
                    <span class="{{ $statusMeta['class'] }}">
                        @if ($normalizedStatus === 'ongoing')
                            <span class="live-dot"></span>
                        @endif
                        {{ $statusMeta['label'] }}
                    </span>
                </div>
            </div>
            <div class="row">
                @foreach ($overviewStats as $stat)
                    <div class="col-md-3 col-sm-6 col-xs-12">
                        <div class="admin-inline-stat" style="width:100%; justify-content:space-between;">
                            <span>{{ $stat['label'] }}</span>
                            <strong>{{ $stat['value'] }}</strong>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="panel panel-default admin-data-card">
                    <div class="panel-heading">
                        <div class="admin-panel-header">
                            <div>
                                <h3>{{ trans('booking.booking_details') }}</h3>
                                <p>Vehicle, route, and booking state.</p>
                            </div>
                        </div>
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <tbody>
                                    <tr>
                                        <th>{{ trans('booking.type') }}/{{ trans('booking.make') }}/{{ trans('booking.model') }}</th>
                                        <td>
                                            <div class="admin-list-stack">
                                                <strong>{{ $bookingData->item->item_Type->name ?? '-' }}</strong>
                                                <span>{{ $bookingData->item->vehicleMake->name ?? '-' }} / {{ $bookingData->item->model ?? '-' }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                    @foreach ($bookingInfo as $row)
                                        <tr>
                                            <th>{{ $row['label'] }}</th>
                                            <td>{{ $row['value'] }}</td>
                                        </tr>
                                    @endforeach
                                    @if (!empty($bookingData->extension?->share_tracking_enabled) && !empty($bookingData->extension?->share_token))
                                        <tr>
                                            <th>Ride share link</th>
                                            <td>
                                                <a href="{{ route('ride-tracking.show', ['token' => $bookingData->extension->share_token]) }}" target="_blank">
                                                    {{ route('ride-tracking.show', ['token' => $bookingData->extension->share_token]) }}
                                                </a>
                                            </td>
                                        </tr>
                                    @endif
                                    @if ($normalizedStatus === 'cancelled')
                                        <tr>
                                            <th>{{ trans('booking.cancellation_reasion') }}</th>
                                            <td>{{ $bookingData->cancellation_reasion }}</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="panel panel-default admin-data-card">
                    <div class="panel-heading">
                        <div class="admin-panel-header">
                            <div>
                                <h3>{{ trans('booking.payments_details') }}</h3>
                                <p>Payment method, status, and settlement distribution.</p>
                            </div>
                        </div>
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <tbody>
                                    <tr>
                                        <th>{{ trans('booking.payment_method') }}</th>
                                        <td>
                                            <span class="{{ $paymentBadgeClass }} badge-custom">
                                                <i class="fas fa-credit-card table-icon"></i>
                                                {{ ucfirst($paymentMethod) }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>{{ trans('booking.payment_status') }}</th>
                                        <td>
                                            @if ($bookingData->payment_status === 'paid')
                                                <span class="badge badge-pill label-success badge-custom">
                                                    <i class="fas fa-check-circle table-icon"></i> Paid
                                                </span>
                                            @elseif ($bookingData->payment_status === 'notpaid')
                                                <span class="badge badge-pill label-danger badge-custom">
                                                    <i class="fas fa-clock table-icon"></i> Pending
                                                </span>
                                            @else
                                                <span>{{ $bookingData->payment_status ?? '-' }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @if(($bookingData->discount_price ?? 0) > 0)
                                        <tr>
                                            <th>{{ trans('booking.discount_amount') }}</th>
                                            <td>
                                                - {{ $bookingData->discount_price }} {{ $bookingData->currency_code }}
                                                @if(!empty($bookingData->coupon_code))
                                                    <strong>({{ $bookingData->coupon_code }})</strong>
                                                @endif
                                                <br>
                                                <small style="color:#888;">{{ trans('booking.discount_paid_note') }}</small>
                                            </td>
                                        </tr>
                                    @endif
                                    @foreach ($paymentRows as $row)
                                        <tr>
                                            <th>{{ $row['label'] }}</th>
                                            <td>{{ $row['value'] }}</td>
                                        </tr>
                                    @endforeach
                                    @if (!empty($bookingData->transaction))
                                        <tr>
                                            <th>{{ trans('booking.transaction_id') }}</th>
                                            <td>
                                                <span class="badge badge-pill badge-dark badge-custom">{{ $bookingData->transaction }}</span>
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="panel panel-default admin-data-card">
                    <div class="panel-heading">
                        <div class="admin-panel-header">
                            <div>
                                <h3>{{ trans('booking.rider_details') }}</h3>
                                <p>Customer identity and contact details.</p>
                            </div>
                        </div>
                    </div>
                    <div class="panel-body">
                        @if ($bookingData->user)
                            <div class="admin-persona" style="margin-bottom:18px;">
                                <div class="admin-persona__avatar">
                                    <a target="_blank" href="{{ route('admin.wallet', ['booking' => $bookingData->user->id, 'user_type' => 'user']) }}">
                                        @if ($bookingData->user->profile_image)
                                            <img src="{{ $bookingData->user->profile_image->getUrl('thumb') }}" class="img-circle_details">
                                        @else
                                            <img src="{{ asset('images/icon/userdefault.jpg') }}" alt="Default Image" class="img-circle_details">
                                        @endif
                                    </a>
                                </div>
                                <div class="admin-persona__meta">
                                    <strong>{{ $bookingData->user->first_name ?? '' }} {{ $bookingData->user->last_name ?? '' }}</strong>
                                    @php $rating = $bookingData->user->avr_guest_rate ?? null; @endphp
                                    <span>{{ $rating ? 'Rating: ' . number_format($rating, 1) . '/5' : 'No rating yet' }}</span>
                                </div>
                            </div>
                        @endif
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <tbody>
                                    <tr>
                                        <th>{{ trans('booking.name') }}</th>
                                        <td>{{ $bookingData->user->first_name ?? '' }} {{ $bookingData->user->last_name ?? '' }}</td>
                                    </tr>
                                    <tr>
                                        <th>{{ trans('booking.email') }}</th>
                                        <td>{{ $bookingData->user->email ?? '' }}</td>
                                    </tr>
                                    <tr>
                                        <th>{{ trans('booking.phone') }}</th>
                                        <td>{{ $bookingData->user->phone_country ?? '' }} {{ $bookingData->user->phone ?? '' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="panel panel-default admin-data-card">
                    <div class="panel-heading">
                        <div class="admin-panel-header">
                            <div>
                                <h3>{{ trans('booking.driver_details') }}</h3>
                                <p>Assigned driver identity and contact information.</p>
                            </div>
                        </div>
                    </div>
                    <div class="panel-body">
                        @if ($bookingData->host)
                            <div class="admin-persona" style="margin-bottom:18px;">
                                <div class="admin-persona__avatar">
                                    <a target="_blank" href="{{ route('admin.overview', ['booking' => $bookingData->host->id, 'user_type' => 'driver']) }}">
                                        @if ($bookingData->host->profile_image)
                                            <img src="{{ $bookingData->host->profile_image->getUrl('thumb') }}" class="img-circle_details">
                                        @else
                                            <img src="{{ asset('images/icon/userdefault.jpg') }}" alt="Default Image" class="img-circle_details">
                                        @endif
                                    </a>
                                </div>
                                <div class="admin-persona__meta">
                                    <strong>{{ $bookingData->host->first_name ?? '' }} {{ $bookingData->host->last_name ?? '' }}</strong>
                                    @php $hostRating = $bookingData->host->ave_host_rate ?? null; @endphp
                                    <span>{{ $hostRating ? 'Rating: ' . number_format($hostRating, 1) . '/5' : 'No rating yet' }}</span>
                                </div>
                            </div>
                        @endif
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <tbody>
                                    <tr>
                                        <th>{{ trans('booking.name') }}</th>
                                        <td>{{ $bookingData->host->first_name ?? '' }} {{ $bookingData->host->last_name ?? '' }}</td>
                                    </tr>
                                    <tr>
                                        <th>{{ trans('booking.email') }}</th>
                                        <td>{{ $bookingData->host->email ?? '' }}</td>
                                    </tr>
                                    <tr>
                                        <th>{{ trans('booking.phone') }}</th>
                                        <td>{{ $bookingData->host->phone_country ?? '' }} {{ $bookingData->host->phone ?? '' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
