<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HireBooking;
use Carbon\Carbon;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HireBookingController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('booking_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $filters = $request->only(['status', 'driver_id', 'rider_id', 'from', 'to']);

        $query = HireBooking::with([
            'driver:id,first_name,last_name,phone',
            'rider:id,first_name,last_name,phone',
        ]);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['driver_id'])) {
            $query->where('driver_id', $filters['driver_id']);
        }

        if (! empty($filters['rider_id'])) {
            $query->where('user_id', $filters['rider_id']);
        }

        if (! empty($filters['from'])) {
            try {
                $from = Carbon::parse($filters['from'])->startOfDay();
                $query->where('start_at', '>=', $from);
            } catch (\Throwable $e) {
                // ignore invalid date
            }
        }

        if (! empty($filters['to'])) {
            try {
                $to = Carbon::parse($filters['to'])->endOfDay();
                $query->where('start_at', '<=', $to);
            } catch (\Throwable $e) {
                // ignore invalid date
            }
        }

        $bookings = $query->orderByDesc('start_at')
            ->paginate(25)
            ->appends($filters);

        $summary = [
            'total' => HireBooking::count(),
            'booked' => HireBooking::where('status', 'booked')->count(),
            'ongoing' => HireBooking::where('status', 'ongoing')->count(),
            'completed' => HireBooking::where('status', 'completed')->count(),
            'cancelled' => HireBooking::where('status', 'cancelled')->count(),
        ];

        return view('admin.hireBookings.index', compact('bookings', 'summary', 'filters'));
    }
}
