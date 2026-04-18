<?php

namespace App\Http\Controllers\Admin;

use App\Models\AppUser;
use App\Models\Booking;
use App\Models\GeneralSetting;
use App\Models\Module;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class HomeController
{
    private const STATUS_ALIASES = [
        'ongoing' => ['ongoing', 'Ongoing'],
        'completed' => ['completed', 'Completed'],
        'cancelled' => ['cancelled', 'Cancelled'],
        'rejected' => ['rejected', 'Rejected'],
        'accepted' => ['accepted', 'Accepted', 'approved', 'Approved'],
    ];

    public function index(Request $request)
    {
        [$rangeStart, $rangeEnd, $rangePreset] = $this->resolveDashboardRange($request);

        $module = Cache::remember('default_module', 3600, function () {
            return Module::where('default_module', '1')->firstOrFail();
        });

        $moduleId = $module->id;
        $moduleName = $module->name;
        $currency = Cache::remember('general_default_currency', 3600, function () {
            return GeneralSetting::where('meta_key', 'general_default_currency')->first();
        });

        $installerWarning = installerExists();
        $metrics = $this->fetchDashboardMetrics($moduleId, $rangeStart, $rangeEnd);
        $latestBookings = Booking::with(['host', 'user', 'item'])
            ->where('module', $moduleId)
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->latest()
            ->take(5)
            ->get();

        $latestUsersData = $this->getLatestUsersData($rangeStart, $rangeEnd);
        $latestBookingsData = $this->getLatestBookingsData($moduleId, $rangeStart, $rangeEnd);
        $pendingCaptainRequests = AppUser::query()
            ->where('user_type', 'driver')
            ->where('host_status', '2')
            ->latest()
            ->take(5)
            ->get();
        $dashboardFilters = [
            'from' => $rangeStart->toDateString(),
            'to' => $rangeEnd->toDateString(),
            'preset' => $rangePreset,
        ];

        return view('home', compact(
            'metrics',
            'currency',
            'moduleName',
            'moduleId',
            'latestBookings',
            'latestUsersData',
            'latestBookingsData',
            'installerWarning',
            'pendingCaptainRequests',
            'dashboardFilters',
        ));
    }

    private function fetchDashboardMetrics($moduleId, Carbon $rangeStart, Carbon $rangeEnd)
    {

        $driverMetrics = Cache::remember("driver_metrics_{$rangeStart->toDateString()}_{$rangeEnd->toDateString()}", 3600, function () use ($rangeStart, $rangeEnd) {
            $today = Carbon::today()->toDateString();

            return AppUser::selectRaw("
                COUNT(*) as total_drivers,
                SUM(CASE WHEN user_type = 'driver' AND status = 1 THEN 1 ELSE 0 END) as active_drivers,
                SUM(CASE WHEN user_type = 'driver' AND status = 0 THEN 1 ELSE 0 END) as inactive_drivers,
                SUM(CASE WHEN user_type = 'driver' AND host_status = '2' THEN 1 ELSE 0 END) as requested_drivers,
                SUM(CASE WHEN user_type = 'driver' AND DATE(created_at) = ? THEN 1 ELSE 0 END) as today_new_drivers,
                SUM(CASE WHEN user_type = 'driver' AND created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as filtered_drivers
            ", [$today, $rangeStart, $rangeEnd])
                ->where('user_type', 'driver')
                ->first();
        });

        $riderMetrics = Cache::remember("rider_metrics_{$rangeStart->toDateString()}_{$rangeEnd->toDateString()}", 3600, function () use ($rangeStart, $rangeEnd) {
            $today = Carbon::today()->toDateString();

            return AppUser::selectRaw("
        COUNT(*) as total_riders,
        SUM(CASE WHEN user_type = 'user' AND status = 1 THEN 1 ELSE 0 END) as active_riders,
        SUM(CASE WHEN user_type = 'user' AND status = 0 THEN 1 ELSE 0 END) as inactive_riders,
        SUM(CASE WHEN user_type = 'user' AND DATE(created_at) = ? THEN 1 ELSE 0 END) as today_new_riders,
        SUM(CASE WHEN user_type = 'user' AND created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as filtered_riders
    ", [$today, $rangeStart, $rangeEnd])
                ->where('user_type', 'user')
                ->first();
        });

        $bookingMetrics = Cache::remember("booking_metrics_{$moduleId}_{$rangeStart->toDateString()}_{$rangeEnd->toDateString()}", 3600, function () use ($moduleId, $rangeStart, $rangeEnd) {
            $today = Carbon::today()->toDateString();
            $ongoing = $this->quotedStatusList(self::STATUS_ALIASES['ongoing']);
            $completed = $this->quotedStatusList(self::STATUS_ALIASES['completed']);
            $cancelled = $this->quotedStatusList(self::STATUS_ALIASES['cancelled']);
            $rejected = $this->quotedStatusList(self::STATUS_ALIASES['rejected']);

            return Booking::selectRaw("
        COUNT(*) as total_paid_bookings,
        SUM(CASE WHEN payment_status = 'paid' AND module = ? AND deleted_at IS NULL AND created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as filtered_paid_bookings,
        SUM(CASE WHEN status IN ({$ongoing}) THEN 1 ELSE 0 END) as running_rides,
        SUM(CASE WHEN status IN ({$completed}) THEN 1 ELSE 0 END) as completed_rides,
        SUM(CASE WHEN status IN ({$cancelled}) THEN 1 ELSE 0 END) as cancelled_rides,
        SUM(CASE WHEN status IN ({$rejected}) THEN 1 ELSE 0 END) as rejected_rides,
        SUM(CASE WHEN status IN ({$ongoing}) AND DATE(created_at) = ? THEN 1 ELSE 0 END) as today_running_rides,
        SUM(CASE WHEN status IN ({$completed}) AND DATE(created_at) = ? THEN 1 ELSE 0 END) as today_completed_rides,
        SUM(CASE WHEN payment_status = 'paid' AND module = ? AND deleted_at IS NULL AND created_at BETWEEN ? AND ? THEN total ELSE 0 END) as total_income,
        SUM(CASE WHEN payment_status = 'paid' AND module = ? AND deleted_at IS NULL AND created_at BETWEEN ? AND ? THEN admin_commission ELSE 0 END) as total_revenue,
        SUM(CASE WHEN payment_status = 'paid' AND module = ? AND deleted_at IS NULL AND DATE(created_at) = ? THEN admin_commission ELSE 0 END) as today_revenue
    ", [
                $moduleId,
                $rangeStart,
                $rangeEnd,
                $today,        // for today_running_rides
                $today,        // for today_completed_rides
                $moduleId,
                $rangeStart,
                $rangeEnd,
                $moduleId,
                $rangeStart,
                $rangeEnd,
                $moduleId,     // for today_revenue (module check)
                $today,         // for today_revenue (date check)
            ])
                ->where('module', $moduleId)
                ->first();
        });

        return [
            'total_drivers' => [
                'chart_title' => 'total drivers',
                'total_number' => $driverMetrics->total_drivers,
            ],
            'total_active_drivers' => [
                'chart_title' => 'total active drivers',
                'total_number' => $driverMetrics->active_drivers,
            ],
            'total_inactive_drivers' => [
                'chart_title' => 'total inactive drivers',
                'total_number' => $driverMetrics->inactive_drivers,
            ],
            'total_requested_drivers' => [
                'chart_title' => 'captain requests',
                'total_number' => $driverMetrics->requested_drivers,
            ],
            'filtered_paid_bookings' => [
                'chart_title' => 'bookings in range',
                'total_number' => $bookingMetrics->filtered_paid_bookings,
            ],
            'total_riders' => [
                'chart_title' => 'total riders',
                'total_number' => $riderMetrics->total_riders,
            ],
            'total_active_riders' => [
                'chart_title' => 'total active riders',
                'total_number' => $riderMetrics->active_riders,
            ],
            'today_new_riders' => [
                'chart_title' => 'today new riders',
                'total_number' => $riderMetrics->today_new_riders,
            ],
            'total_paid_bookings' => [
                'chart_title' => 'total paid bookings',
                'total_number' => $bookingMetrics->total_paid_bookings,
            ],
            'running_rides' => [
                'chart_title' => 'running rides',
                'total_number' => $bookingMetrics->running_rides,
            ],
            'completed_rides' => [
                'chart_title' => 'completed rides',
                'total_number' => $bookingMetrics->completed_rides,
            ],
            'cancelled_rides' => [
                'chart_title' => 'cancelled rides',
                'total_number' => $bookingMetrics->cancelled_rides,
            ],
            'rejected_rides' => [
                'chart_title' => 'rejected rides',
                'total_number' => $bookingMetrics->rejected_rides,
            ],
            'today_new_drivers' => [
                'chart_title' => 'today new drivers',
                'total_number' => $driverMetrics->today_new_drivers,
            ],
            'today_running_rides' => [
                'chart_title' => 'today running rides',
                'total_number' => $bookingMetrics->today_running_rides,
            ],
            'today_completed_rides' => [
                'chart_title' => 'today completed rides',
                'total_number' => $bookingMetrics->today_completed_rides,
            ],
            'total_revenue' => [
                'chart_title' => 'total revenue',
                'total_number' => $bookingMetrics->total_revenue,
            ],
            'today_revenue' => [
                'chart_title' => 'todays revenue',
                'total_number' => $bookingMetrics->today_revenue,
            ],
            'total_income' => [
                'chart_title' => 'total income',
                'total_number' => $bookingMetrics->total_income,
            ],
        ];
    }

    private function getLatestUsersData(Carbon $rangeStart, Carbon $rangeEnd)
    {
        return Cache::remember("latest_users_data_{$rangeStart->toDateString()}_{$rangeEnd->toDateString()}", 3600, function () use ($rangeStart, $rangeEnd) {
            return AppUser::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->whereBetween('created_at', [$rangeStart, $rangeEnd])
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(function ($record) {
                    return [
                        'date' => $record->date,
                        'count' => $record->count,
                    ];
                });
        });
    }

    private function getLatestBookingsData($moduleId, Carbon $rangeStart, Carbon $rangeEnd)
    {
        return Cache::remember("latest_bookings_data_{$moduleId}_{$rangeStart->toDateString()}_{$rangeEnd->toDateString()}", 3600, function () use ($moduleId, $rangeStart, $rangeEnd) {
            return Booking::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('module', $moduleId)
                ->whereBetween('created_at', [$rangeStart, $rangeEnd])
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(function ($record) {
                    return [
                        'date' => $record->date,
                        'count' => $record->count,
                    ];
                });
        });
    }

    private function resolveDashboardRange(Request $request): array
    {
        $preset = $request->string('range')->toString() ?: '7d';
        $fromInput = $request->input('from');
        $toInput = $request->input('to');

        try {
            if ($fromInput || $toInput) {
                $start = $fromInput ? Carbon::parse($fromInput)->startOfDay() : Carbon::now()->subDays(6)->startOfDay();
                $end = $toInput ? Carbon::parse($toInput)->endOfDay() : Carbon::now()->endOfDay();
                return [$start, $end, 'custom'];
            }
        } catch (\Throwable $e) {
            // fall back to preset
        }

        return match ($preset) {
            'today' => [Carbon::today()->startOfDay(), Carbon::today()->endOfDay(), 'today'],
            '30d' => [Carbon::now()->subDays(29)->startOfDay(), Carbon::now()->endOfDay(), '30d'],
            default => [Carbon::now()->subDays(6)->startOfDay(), Carbon::now()->endOfDay(), '7d'],
        };
    }

    private function quotedStatusList(array $statuses): string
    {
        return collect($statuses)
            ->map(fn ($status) => DB::getPdo()->quote($status))
            ->implode(', ');
    }

    public function deleteInstaller()
    {
        $files = [
            app_path('Http/Controllers/InstallerController.php'),
            base_path('installer/rideon.sql'),
        ];

        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        $installerViewDir = resource_path('views/installer');

        if (is_dir($installerViewDir)) {
            $this->deleteDirectory($installerViewDir);
        }

        return back()->with('success', 'Installer files deleted successfully.');
    }

    private function deleteDirectory($dir)
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = scandir($dir);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir.DIRECTORY_SEPARATOR.$item;

            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
