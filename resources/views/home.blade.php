@extends('layouts.admin')

@section('content')
    @php
        $currentDate = date('Y-m-d');
        $dashboardBrand = $siteName ?? 'QRIDES';
        $heroMetrics = [
            ['key' => 'total_drivers', 'label' => 'Drivers', 'icon' => 'fa-car', 'route' => route('admin.drivers.index')],
            ['key' => 'total_riders', 'label' => 'Riders', 'icon' => 'fa-users', 'route' => route('admin.app-users.index', ['user_type' => 'user'])],
            ['key' => 'running_rides', 'label' => 'Running rides', 'icon' => 'fa-road', 'route' => route('admin.bookings.index', ['status' => 'ongoing'])],
            ['key' => 'total_revenue', 'label' => 'Revenue', 'icon' => 'fa-line-chart', 'route' => route('admin.finance')],
        ];
        $rideStats = [
            ['key' => 'today_running_rides', 'label' => 'Today running', 'route' => route('admin.bookings.index', ['from' => $currentDate, 'to' => $currentDate])],
            ['key' => 'today_completed_rides', 'label' => 'Today completed', 'route' => route('admin.bookings.index', ['from' => $currentDate, 'to' => $currentDate])],
            ['key' => 'completed_rides', 'label' => 'Completed', 'route' => route('admin.bookings.index', ['status' => 'completed'])],
            ['key' => 'cancelled_rides', 'label' => 'Cancelled', 'route' => route('admin.bookings.index', ['status' => 'cancelled'])],
            ['key' => 'rejected_rides', 'label' => 'Rejected', 'route' => route('admin.bookings.index', ['status' => 'rejected'])],
            ['key' => 'total_requested_drivers', 'label' => 'Pending drivers', 'route' => route('admin.drivers.index', ['host_status' => '2'])],
        ];
        $statusPalette = [
            'pending' => 'status-pending',
            'accepted' => 'status-active',
            'approved' => 'status-active',
            'ongoing' => 'status-active',
            'completed' => 'status-complete',
            'cancelled' => 'status-danger',
            'rejected' => 'status-danger',
        ];
    @endphp

    <div class="content dashboard-content">
        <div class="container-fluid">
            @if (session('status'))
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    {{ session('status') }}
                </div>
            @endif

            @if ($installerWarning)
                <div class="alert alert-danger">
                    Installer files are still present. Remove them to lock down production.
                    <a href="{{ route('admin.deleteInstaller') }}" class="btn btn-danger btn-sm pull-right">Delete installer files</a>
                </div>
            @endif

            <div class="dashboard-hero">
                <div class="dashboard-hero__copy">
                    <span class="dashboard-hero__eyebrow">Operations</span>
                    <h1>{{ $dashboardBrand }} control center</h1>
                    <p>Monitor drivers, riders, live ride flow, and revenue from a single QRIDES operations workspace.</p>
                </div>
                <div class="dashboard-hero__meta">
                    <div class="dashboard-hero__meta-card">
                        <span>Today</span>
                        <strong>{{ now()->format('d M Y') }}</strong>
                    </div>
                    <div class="dashboard-hero__meta-card">
                        <span>Service</span>
                        <strong>{{ $moduleName }}</strong>
                    </div>
                </div>
            </div>

            <div class="row dashboard-grid">
                @foreach ($heroMetrics as $card)
                    @php $value = $metrics[$card['key']]['total_number'] ?? 0; @endphp
                    <div class="col-sm-6 col-lg-3">
                        <a href="{{ $card['route'] }}" class="dashboard-stat-card">
                            <span class="dashboard-stat-card__icon"><i class="fa {{ $card['icon'] }}"></i></span>
                            <span class="dashboard-stat-card__label">{{ $card['label'] }}</span>
                            <strong class="dashboard-stat-card__value">
                                @if (in_array($card['key'], ['total_revenue']))
                                    {{ number_format($value, 2) }} {{ $currency->meta_value ?? '' }}
                                @else
                                    {{ number_format($value) }}
                                @endif
                            </strong>
                            <span class="dashboard-stat-card__link">Open details</span>
                        </a>
                    </div>
                @endforeach
            </div>

            <div class="dashboard-status-strip">
                @foreach ($rideStats as $card)
                    <a href="{{ $card['route'] }}" class="dashboard-status-pill">
                        <span>{{ $card['label'] }}</span>
                        <strong>{{ number_format($metrics[$card['key']]['total_number'] ?? 0) }}</strong>
                    </a>
                @endforeach
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="settings-card dashboard-panel">
                        <div class="settings-card__header">
                            <div>
                                <h3>Latest rides</h3>
                                <p>Recent paid bookings created this year.</p>
                            </div>
                            <a href="{{ route('admin.bookings.index') }}" class="btn btn-default btn-sm">See all rides</a>
                        </div>

                        <div class="table-responsive">
                            <table class="table dashboard-table">
                                <thead>
                                    <tr>
                                        <th>Booking</th>
                                        <th>Created</th>
                                        <th>Driver</th>
                                        <th>Rider</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($latestBookings as $entry)
                                        @php
                                            $normalizedStatus = strtolower($entry->status ?? '');
                                            $badgeClass = $statusPalette[$normalizedStatus] ?? 'status-neutral';
                                        @endphp
                                        <tr>
                                            <td>
                                                <a href="{{ route('admin.bookings.show', $entry->id) }}">{{ $entry->token }}</a>
                                            </td>
                                            <td>{{ optional($entry->created_at)->format('d M Y, h:i A') ?? 'N/A' }}</td>
                                            <td>
                                                @if ($entry->host)
                                                    <a target="_blank" href="{{ route('admin.driver.profile', ['driver_id' => $entry->host->id]) }}">
                                                        {{ $entry->host->first_name }} {{ $entry->host->last_name }}
                                                    </a>
                                                @else
                                                    <span>--</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($entry->user)
                                                    <a target="_blank" href="{{ route('admin.app-users.show', $entry->user->id) }}">
                                                        {{ $entry->user->first_name ?? '' }} {{ $entry->user->last_name ?? '' }}
                                                    </a>
                                                @else
                                                    <span>--</span>
                                                @endif
                                            </td>
                                            <td>{{ ($currency->meta_value ?? '') . ' ' . ($entry->total ?? 'N/A') }}</td>
                                            <td>
                                                <span class="dashboard-status-badge {{ $badgeClass }}">
                                                    {{ $entry->status ?? 'N/A' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6">No entries found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="settings-card dashboard-panel dashboard-panel--compact">
                        <div class="settings-card__header">
                            <div>
                                <h3>Revenue snapshot</h3>
                                <p>Quick view of income and commission totals.</p>
                            </div>
                        </div>
                        <div class="dashboard-summary-list">
                            <div class="dashboard-summary-list__item">
                                <span>Total income</span>
                                <strong>{{ number_format($metrics['total_income']['total_number'] ?? 0, 2) }} {{ $currency->meta_value ?? '' }}</strong>
                            </div>
                            <div class="dashboard-summary-list__item">
                                <span>Total revenue</span>
                                <strong>{{ number_format($metrics['total_revenue']['total_number'] ?? 0, 2) }} {{ $currency->meta_value ?? '' }}</strong>
                            </div>
                            <div class="dashboard-summary-list__item">
                                <span>Today revenue</span>
                                <strong>{{ number_format($metrics['today_revenue']['total_number'] ?? 0, 2) }} {{ $currency->meta_value ?? '' }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="settings-card dashboard-panel">
                        <div class="settings-card__header">
                            <div>
                                <h3>New users</h3>
                                <p>Seven day user sign-up trend.</p>
                            </div>
                        </div>
                        <div class="dashboard-chart-wrap">
                            <canvas id="chBarUsers" class="chart-canvas"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="settings-card dashboard-panel">
                        <div class="settings-card__header">
                            <div>
                                <h3>Ride creation trend</h3>
                                <p>Seven day booking creation trend.</p>
                            </div>
                        </div>
                        <div class="dashboard-chart-wrap">
                            <canvas id="chLine" class="chart-canvas"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @parent
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.min.js"></script>
    <script>
        const colors = {
            teal: '#f97316',
            tealSoft: 'rgba(249, 115, 22, 0.16)',
            slate: '#475569',
            border: '#e7d8c7'
        };

        const latestUsersData = @json($latestUsersData);
        const latestBookingsData = @json($latestBookingsData);

        const labelsUsers = latestUsersData.map(record => record.date);
        const dataUsers = latestUsersData.map(record => record.count);
        const labelsBookings = latestBookingsData.map(record => record.date);
        const dataBookings = latestBookingsData.map(record => record.count);

        const chBarUsers = document.getElementById('chBarUsers');
        if (chBarUsers) {
            new Chart(chBarUsers, {
                type: 'bar',
                data: {
                    labels: labelsUsers,
                    datasets: [{
                        label: 'Users',
                        data: dataUsers,
                        backgroundColor: colors.teal,
                        borderRadius: 8
                    }]
                },
                options: {
                    legend: { display: false },
                    scales: {
                        xAxes: [{
                            gridLines: { display: false },
                            ticks: { fontColor: colors.slate }
                        }],
                        yAxes: [{
                            ticks: { beginAtZero: true, fontColor: colors.slate },
                            gridLines: { color: colors.border }
                        }]
                    },
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }

        const chLine = document.getElementById('chLine');
        if (chLine) {
            new Chart(chLine, {
                type: 'line',
                data: {
                    labels: labelsBookings,
                    datasets: [{
                        data: dataBookings,
                        backgroundColor: colors.tealSoft,
                        borderColor: colors.teal,
                        borderWidth: 3,
                        pointBackgroundColor: colors.teal,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    legend: { display: false },
                    scales: {
                        xAxes: [{
                            gridLines: { display: false },
                            ticks: { fontColor: colors.slate }
                        }],
                        yAxes: [{
                            ticks: { beginAtZero: true, fontColor: colors.slate },
                            gridLines: { color: colors.border }
                        }]
                    },
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
    </script>
@endsection
