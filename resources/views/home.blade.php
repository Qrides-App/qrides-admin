@extends('layouts.admin')

@section('content')
    @php
        $currentDate = date('Y-m-d');
        $dashboardBrand = $siteName ?? 'QRIDES';
        $heroMetrics = [
            ['key' => 'total_active_drivers', 'label' => 'Active drivers', 'subtext' => 'Live captain base', 'icon' => 'fa-car', 'route' => route('admin.drivers.index', ['status' => '1'])],
            ['key' => 'total_requested_drivers', 'label' => 'Captain requests', 'subtext' => 'Pending approvals', 'icon' => 'fa-id-card', 'route' => route('admin.drivers.index', ['host_status' => '2'])],
            ['key' => 'filtered_paid_bookings', 'label' => 'Bookings in range', 'subtext' => 'Filtered ride volume', 'icon' => 'fa-road', 'route' => route('admin.bookings.index', ['from' => $dashboardFilters['from'], 'to' => $dashboardFilters['to']])],
            ['key' => 'total_revenue', 'label' => 'Revenue in range', 'subtext' => 'Admin earnings', 'icon' => 'fa-line-chart', 'route' => route('admin.finance')],
        ];
        $rideStats = [
            ['key' => 'today_running_rides', 'label' => 'Today running', 'route' => route('admin.bookings.index', ['from' => $currentDate, 'to' => $currentDate])],
            ['key' => 'today_completed_rides', 'label' => 'Today completed', 'route' => route('admin.bookings.index', ['from' => $currentDate, 'to' => $currentDate])],
            ['key' => 'completed_rides', 'label' => 'Completed', 'route' => route('admin.bookings.index', ['status' => 'completed'])],
            ['key' => 'cancelled_rides', 'label' => 'Cancelled', 'route' => route('admin.bookings.index', ['status' => 'cancelled'])],
            ['key' => 'rejected_rides', 'label' => 'Rejected', 'route' => route('admin.bookings.index', ['status' => 'rejected'])],
            ['key' => 'today_new_riders', 'label' => 'New riders today', 'route' => route('admin.app-users.index', ['user_type' => 'user'])],
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

            <form method="get" class="dashboard-filter-bar">
                <div class="dashboard-filter-bar__group">
                    <a href="{{ route('admin.home', ['range' => 'today']) }}" class="dashboard-filter-chip {{ $dashboardFilters['preset'] === 'today' ? 'is-active' : '' }}">Today</a>
                    <a href="{{ route('admin.home', ['range' => '7d']) }}" class="dashboard-filter-chip {{ $dashboardFilters['preset'] === '7d' ? 'is-active' : '' }}">7 days</a>
                    <a href="{{ route('admin.home', ['range' => '30d']) }}" class="dashboard-filter-chip {{ $dashboardFilters['preset'] === '30d' ? 'is-active' : '' }}">30 days</a>
                </div>
                <div class="dashboard-filter-bar__fields">
                    <input type="date" class="form-control" name="from" value="{{ $dashboardFilters['from'] }}">
                    <input type="date" class="form-control" name="to" value="{{ $dashboardFilters['to'] }}">
                    <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                    <a href="{{ route('admin.home', ['range' => '7d']) }}" class="btn btn-default btn-sm">Reset</a>
                </div>
            </form>

            <div class="dashboard-hero">
                <div class="dashboard-hero__copy">
                    <span class="dashboard-hero__eyebrow">Operations</span>
                    <h1>{{ $dashboardBrand }} control center</h1>
                    <p>Track captain approvals, bookings, riders, and revenue for the selected dashboard range.</p>
                </div>
                <div class="dashboard-hero__meta">
                    <div class="dashboard-hero__meta-card">
                        <span>Range</span>
                        <strong>{{ \Carbon\Carbon::parse($dashboardFilters['from'])->format('d M') }} - {{ \Carbon\Carbon::parse($dashboardFilters['to'])->format('d M Y') }}</strong>
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
                            <span class="dashboard-stat-card__meta">{{ $card['subtext'] }}</span>
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
                <div class="col-md-7">
                    <div class="settings-card dashboard-panel dashboard-panel--compact">
                        <div class="settings-card__header">
                            <div>
                                <h3>Pending captain applications</h3>
                                <p>Newest driver requests waiting for admin review.</p>
                            </div>
                            <a href="{{ route('admin.drivers.index', ['host_status' => '2']) }}" class="btn btn-default btn-sm">Open requests</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table dashboard-table">
                                <thead>
                                    <tr>
                                        <th>Captain</th>
                                        <th>Phone</th>
                                        <th>Applied</th>
                                        <th>Docs</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($pendingCaptainRequests as $driver)
                                        <tr>
                                            <td>{{ $driver->first_name }} {{ $driver->last_name }}</td>
                                            <td>{{ ($driver->phone_country ?? '') . ' ' . ($driver->phone ?? '') }}</td>
                                            <td>{{ optional($driver->created_at)->format('d M Y') }}</td>
                                            <td>
                                                <span class="dashboard-status-badge {{ $driver->document_verify ? 'status-active' : 'status-pending' }}">
                                                    {{ $driver->document_verify ? 'Verified' : 'Pending docs' }}
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <a href="{{ route('admin.driver.document', $driver->id) }}" class="btn btn-default btn-xs">Review</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5">No pending captain applications.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="settings-card dashboard-panel">
                        <div class="settings-card__header">
                            <div>
                                <h3>Latest rides</h3>
                                <p>Recent paid bookings within the selected range.</p>
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
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="settings-card dashboard-panel dashboard-panel--compact">
                        <div class="settings-card__header">
                            <div>
                                <h3>Revenue snapshot</h3>
                                <p>Selected range and today comparison.</p>
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
                <div class="col-md-4">
                    <div class="settings-card dashboard-panel">
                        <div class="settings-card__header">
                            <div>
                                <h3>User sign-up trend</h3>
                                <p>New users within the selected range.</p>
                            </div>
                        </div>
                        <div class="dashboard-chart-wrap">
                            <canvas id="chBarUsers" class="chart-canvas"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="settings-card dashboard-panel">
                        <div class="settings-card__header">
                            <div>
                                <h3>Ride creation trend</h3>
                                <p>Bookings created within the selected range.</p>
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
            teal: '#2D6BEE',
            tealSoft: 'rgba(45, 107, 238, 0.16)',
            slate: '#475569',
            border: '#d7e3f6'
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
