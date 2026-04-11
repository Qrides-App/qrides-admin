<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Models\AppUser;
use App\Models\Booking;
use App\Models\BookingCancellationReason;
use App\Models\GeneralSetting;
use App\Models\Modern\Item;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OrderController extends Controller
{
    public function index(Request $request)
    {

        if (auth()->check()) {
            $user = auth()->user();
            $vendorId = $user->id;
        }

        $from = $request->input('from');
        $to = $request->input('to');
        $status = $request->input('status');
        $customer = $request->input('customer');
        $property = $request->input('item');
        // $vendorId = auth()->user()->id;

        $query = Booking::with(['host', 'user', 'item'])
            ->where('payment_status', 'paid')
            ->where('host_id', $vendorId);

        if ($from && $to) {
            $query->whereBetween('updated_at', [$from.' 00:00:00', $to.' 23:59:59']);
        } elseif ($from) {
            $query->where('updated_at', '>=', $from.' 00:00:00');
        } elseif ($to) {
            $query->where('updated_at', '<=', $to.' 23:59:59');
        }

        if ($customer) {
            $query->where('userid', $customer);
        }

        if ($property) {
            $query->where('itemid', $property);
        }

        $statusCounts = [
            'live' => (clone $query)->where('status', '!=', 'trash')->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'confirmed' => (clone $query)->where('status', 'confirmed')->count(),
            'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
            'declined' => (clone $query)->where('status', 'declined')->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'refunded' => (clone $query)->where('status', 'refunded')->count(),
            'trash' => (clone $query)->onlyTrashed()->count(),
        ];

        if ($status) {
            $query->where('status', $status);
        }

        if ($status == 'pending') {
            $query->where('status', 'pending');
        }
        if ($status == 'confirmed') {
            $query->where('status', 'confirmed');
        }
        if ($status == 'cancelled') {
            $query->where('status', 'cancelled');
        }
        if ($status == 'declined') {
            $query->where('status', 'declined');
        }
        if ($status == 'completed') {
            $query->where('status', 'completed');
        }
        if ($status == 'refunded') {
            $query->where('status', 'refunded');
        }
        $query->orderBy('created_at', 'desc');
        // count price and customer
        $filteredBookingsQuery = clone $query;

        $totalSum = $filteredBookingsQuery->sum('total');
        $totalBookings = $filteredBookingsQuery->count();
        $totalCustomers = $filteredBookingsQuery->distinct('userid')->count('userid');

        $totalEarningsQuery = clone $query;
        $totalEarnings = $totalEarningsQuery->whereIn('status', ['Confirmed', 'Completed'])->sum('total');

        $totalRefundedQuery = clone $query;
        $totalRefunded = $totalRefundedQuery->where('status', 'Cancelled')->sum('refundableAmount');

        $bookings = $query->paginate(50);

        $currency_code = Booking::first();

        $customerData = AppUser::find($customer);
        if ($customerData) {
            $searchfieldCustomerName = $customerData->first_name.' '.$customerData->last_name;
            $searchfieldCustomerId = $customerData->id;
        } else {
            $searchfieldCustomerName = 'All';
            $searchfieldCustomerId = '';
        }

        $itemData = Item::find($property);
        if ($itemData) {
            $searchfieldItemName = $itemData->title;
            $searchfieldItemId = $itemData->id;
        } else {
            $searchfieldItemName = 'All';
            $searchfieldItemId = '';
        }

        $general_default_currency = GeneralSetting::where('meta_key', 'general_default_currency')->first();

        return view('vendor.orders.index', compact('bookings', 'statusCounts', 'totalBookings', 'totalCustomers', 'totalEarnings', 'totalRefunded', 'totalSum', 'searchfieldCustomerId', 'searchfieldCustomerName', 'searchfieldItemName', 'searchfieldItemId', 'customerData', 'general_default_currency'));
    }

    public function create()
    {

        $hosts = User::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('vendor.bookings.create', compact('hosts'));
    }

    public function store(StoreBookingRequest $request)
    {
        // print_r($request->all());
        // die;
        $booking = Booking::create($request->all());

        return redirect()->route('vendor.bookings.index');
    }

    public function edit(Booking $booking)
    {

        $hosts = User::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $booking->load('host');

        return view('vendor.bookings.edit', compact('booking', 'hosts'));
    }

    public function update(UpdateBookingRequest $request, Booking $booking)
    {
        $booking->update($request->all());

        return redirect()->route('vendor.bookings.index');
    }

    public function show($id)
    {
        $bookingId = $id;

        $Data = Booking::with(['host:id,first_name,last_name', 'user:id,first_name,last_name,email,phone,phone_country', 'item:id,title,address'])
            ->where('id', $bookingId)
            ->orderBy('id', 'desc')
            ->first();
        $general_default_currency = GeneralSetting::where('meta_key', 'general_default_currency')->first();

        return view('vendor.orders.show', compact('Data', 'general_default_currency'));
    }

    // customer
    public function customerproperty(Request $request)
    {
        $customerName = $request->input('q'); // Retrieve the search term from the request

        $customerproperty = Property::join('app_users', 'properties.userid_id', '=', 'app_users.id')
            ->select('properties.*', 'app_users.first_name as customer_name')
            ->where('app_users.first_name', 'like', '%'.$customerName.'%') // Filter by customer name
            ->distinct()
            ->get();

        $data = [];
        foreach ($customerproperty as $property) {
            $data[] = [
                'id' => $property->userid_id,
                'name' => $property->property_name,
                'customer_name' => $property->customer_name,
            ];
        }

        return response()->json($data);
    }

    public function confirmOrder(Request $request)
    {
        $userId = auth()->user()->id;

        $appUser = AppUser::where('id', $userId)->first();

        if (! $appUser || ! $appUser->token) {
            return response()->json([
                'status' => 400,
                'message' => 'Token not found for the authenticated user.',
            ], 400);
        }

        $data = [
            'booking_id' => $request->input('id'),
            'token' => $appUser->token,
        ];

        $response = Http::post(url('api/v1/confirmBookingByHost'), $data);

        return response()->json($response->json());
    }

    public function cancelOrder(Request $request)
    {
        $userId = auth()->user()->id;
        $cancellationReason = $request->input('cancellation_reason_id');
        $appUser = AppUser::where('id', $userId)->first();

        if (! $appUser || ! $appUser->token) {
            return response()->json([
                'status' => 400,
                'message' => 'Token not found for the authenticated user.',
            ], 400);
        }

        $data = [
            'booking_id' => $request->input('booking_id'),
            'token' => $appUser->token,
            'cancellation_reasion' => $cancellationReason,
        ];

        $response = Http::post(url('api/v1/cancelBookingByHost'), $data);

        return response()->json($response->json());
    }

    public function getCancellationReasons(Request $request)
    {

        $reasons = BookingCancellationReason::where('user_type', 'host')->get();

        return response()->json([
            'status' => 200,
            'data' => $reasons,
        ]);
    }
}
