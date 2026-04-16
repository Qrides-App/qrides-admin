<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $this->seedGeneralSettings($now);

        $users = $this->seedAppUsers($now);
        $riderIds = $users['riders'];
        $driverIds = $users['drivers'];

        $cityIds = $this->seedCities($now);
        $itemTypeIds = $this->seedItemTypes($now);
        $makeIds = $this->seedVehicleMakes($now);

        $itemIds = $this->seedItems($driverIds, $cityIds, $itemTypeIds, $makeIds, $now);
        $bookingIds = $this->seedBookings($driverIds, $riderIds, $itemIds, $now);

        $this->seedBookingExtensions($bookingIds, $now);
        $this->seedPayoutMethods($now);
        $this->seedPayouts($driverIds, $now);
        $this->seedCoupons($now);
        $this->seedSupportTickets($riderIds, $now);
        $this->seedHireBookings($driverIds, $riderIds, $itemIds, $now);
    }

    private function seedGeneralSettings($now): void
    {
        if (!Schema::hasTable('general_settings')) {
            return;
        }

        $settings = [
            ['meta_key' => 'general_name', 'meta_value' => 'Crave Cabs Demo'],
            ['meta_key' => 'general_description', 'meta_value' => 'Demo seed data for admin walkthrough'],
            ['meta_key' => 'general_email', 'meta_value' => 'demo@cravecabs.local'],
            ['meta_key' => 'general_phone', 'meta_value' => '+91 99999 11111'],
            ['meta_key' => 'general_default_currency', 'meta_value' => 'INR'],
            ['meta_key' => 'hire_currency', 'meta_value' => 'INR'],
            ['meta_key' => 'hire_rate_per_hour', 'meta_value' => '450'],
            ['meta_key' => 'push_notification_status', 'meta_value' => 'firebase'],
            ['meta_key' => 'firebase_server_key', 'meta_value' => 'demo-firebase-key'],
        ];

        foreach ($settings as $setting) {
            $row = $this->filterColumns('general_settings', array_merge($setting, [
                'module' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
            DB::table('general_settings')->updateOrInsert(
                ['meta_key' => $setting['meta_key']],
                $row
            );
        }
    }

    private function seedAppUsers($now): array
    {
        $result = ['drivers' => [], 'riders' => []];

        if (!Schema::hasTable('app_users')) {
            return $result;
        }
        $packageId = $this->resolvePackageId($now);

        $demoUsers = [
            ['first_name' => 'Ravi', 'last_name' => 'Driver', 'email' => 'driver1@demo.local', 'phone' => '9000000001', 'user_type' => 'driver'],
            ['first_name' => 'Aman', 'last_name' => 'Driver', 'email' => 'driver2@demo.local', 'phone' => '9000000002', 'user_type' => 'driver'],
            ['first_name' => 'Neha', 'last_name' => 'Driver', 'email' => 'driver3@demo.local', 'phone' => '9000000003', 'user_type' => 'driver'],
            ['first_name' => 'Ira', 'last_name' => 'Rider', 'email' => 'rider1@demo.local', 'phone' => '9000000011', 'user_type' => 'user'],
            ['first_name' => 'Karan', 'last_name' => 'Rider', 'email' => 'rider2@demo.local', 'phone' => '9000000012', 'user_type' => 'user'],
            ['first_name' => 'Sonal', 'last_name' => 'Rider', 'email' => 'rider3@demo.local', 'phone' => '9000000013', 'user_type' => 'user'],
        ];

        foreach ($demoUsers as $user) {
            $raw = [
                'first_name' => $user['first_name'],
                'middle' => null,
                'middle1' => null,
                'last_name' => $user['last_name'],
                'email' => $user['email'],
                'phone' => $user['phone'],
                'phone_country' => '+91',
                'default_country' => 'IN',
                'intro' => 'Demo account',
                'intro1' => 'Demo account',
                'language' => 'en',
                'langauge1' => 'en',
                'country' => 'India',
                'country1' => 'India',
                'password' => Hash::make('password'),
                'wallet' => 1500,
                'otp_value' => 0,
                'token' => Str::random(20),
                'reset_token' => 0,
                'verified' => 1,
                'document_verify' => $user['user_type'] === 'driver' ? 1 : 0,
                'phone_verify' => 1,
                'email_verify' => 1,
                'login_type' => 'email',
                'user_type' => $user['user_type'],
                'host_status' => $user['user_type'] === 'driver' ? '1' : '0',
                'birthdate' => '1994-01-01',
                'birthdate_1' => '1994-01-01',
                'social_id' => null,
                'ave_host_rate' => 4.6,
                'avr_guest_rate' => 4.4,
                'status' => 1,
                'fcm' => null,
                'sms_notification' => 1,
                'email_notification' => 1,
                'push_notification' => 1,
                'sms_notification1' => 1,
                'email_notification1' => 1,
                'push_notification1' => 1,
                'device_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if (!is_null($packageId)) {
                $raw['package_id'] = $packageId;
            }

            $row = $this->filterColumns('app_users', $raw);
            DB::table('app_users')->updateOrInsert(['email' => $user['email']], $row);

            $id = DB::table('app_users')->where('email', $user['email'])->value('id');
            if ($id) {
                if ($user['user_type'] === 'driver') {
                    $result['drivers'][] = (int) $id;
                } else {
                    $result['riders'][] = (int) $id;
                }
            }
        }

        return $result;
    }

    private function seedCities($now): array
    {
        if (!Schema::hasTable('cities')) {
            return [];
        }

        $cities = [
            ['city_name' => 'Bengaluru', 'country_code' => 'IN', 'region' => 'Karnataka', 'latitude' => '12.9716', 'longtitude' => '77.5946'],
            ['city_name' => 'Mumbai', 'country_code' => 'IN', 'region' => 'Maharashtra', 'latitude' => '19.0760', 'longtitude' => '72.8777'],
            ['city_name' => 'Delhi', 'country_code' => 'IN', 'region' => 'Delhi', 'latitude' => '28.6139', 'longtitude' => '77.2090'],
        ];

        $ids = [];
        foreach ($cities as $city) {
            $row = $this->filterColumns('cities', array_merge($city, [
                'description' => $city['city_name'] . ' demo coverage zone',
                'status' => 1,
                'module' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]));

            DB::table('cities')->updateOrInsert(
                ['city_name' => $city['city_name']],
                $row
            );

            $ids[] = (int) DB::table('cities')->where('city_name', $city['city_name'])->value('id');
        }

        return array_values(array_filter($ids));
    }

    private function seedItemTypes($now): array
    {
        if (!Schema::hasTable('rental_item_types')) {
            return [];
        }

        $types = [
            ['name' => 'Sedan', 'description' => 'Comfort ride', 'status' => 1],
            ['name' => 'SUV', 'description' => 'Family ride', 'status' => 1],
            ['name' => 'Mini', 'description' => 'City compact', 'status' => 1],
        ];

        $ids = [];
        foreach ($types as $type) {
            $row = $this->filterColumns('rental_item_types', array_merge($type, [
                'module' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
            DB::table('rental_item_types')->updateOrInsert(['name' => $type['name']], $row);
            $ids[] = (int) DB::table('rental_item_types')->where('name', $type['name'])->value('id');
        }

        return array_values(array_filter($ids));
    }

    private function seedVehicleMakes($now): array
    {
        if (!Schema::hasTable('rental_item_make')) {
            return [];
        }

        $makes = [
            ['name' => 'Hyundai', 'description' => 'Hyundai lineup'],
            ['name' => 'Maruti', 'description' => 'Maruti lineup'],
            ['name' => 'Toyota', 'description' => 'Toyota lineup'],
        ];

        $ids = [];
        foreach ($makes as $make) {
            $row = $this->filterColumns('rental_item_make', array_merge($make, [
                'status' => 1,
                'module' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
            DB::table('rental_item_make')->updateOrInsert(['name' => $make['name']], $row);
            $ids[] = (int) DB::table('rental_item_make')->where('name', $make['name'])->value('id');
        }

        return array_values(array_filter($ids));
    }

    private function seedItems(array $driverIds, array $cityIds, array $itemTypeIds, array $makeIds, $now): array
    {
        if (!Schema::hasTable('rental_items') || empty($driverIds)) {
            return [];
        }

        $items = [];
        foreach ($driverIds as $idx => $driverId) {
            $token = 'ITM-DEMO-' . ($idx + 1);
            $itemTypeId = $itemTypeIds[$idx % max(count($itemTypeIds), 1)] ?? null;
            $cityId = $cityIds[$idx % max(count($cityIds), 1)] ?? null;
            $makeId = $makeIds[$idx % max(count($makeIds), 1)] ?? null;

            $raw = [
                'token' => $token,
                'title' => 'Demo Cab ' . ($idx + 1),
                'description' => 'Seeded vehicle for admin demo flow',
                'item_rating' => 4.5,
                'average_speed_kmph' => 45,
                'mobile' => '90000000' . ($idx + 21),
                'price' => 350,
                'currency' => 'INR',
                'address' => 'Main Road',
                'state_region' => 'State',
                'city_name' => 'Demo City',
                'city' => $cityId,
                'country' => 'India',
                'zip_postal_code' => '560001',
                'longitude' => 77.59 + $idx,
                'latitude' => 12.97 + $idx,
                'userid_id' => $driverId,
                'item_type_id' => $itemTypeId,
                'place_id' => $cityId,
                'make' => $makeId,
                'model' => 'Model-' . ($idx + 1),
                'registration_number' => 'KA0' . ($idx + 1) . 'DEMO' . (100 + $idx),
                'service_type' => 'car',
                'module' => 2,
                'is_featured' => 1,
                'is_verified' => 1,
                'status' => 1,
                'views_count' => 10 + $idx,
                'step_progress' => 100,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $row = $this->filterColumns('rental_items', $raw);
            DB::table('rental_items')->updateOrInsert(['token' => $token], $row);
            $items[] = (int) DB::table('rental_items')->where('token', $token)->value('id');
        }

        return array_values(array_filter($items));
    }

    private function seedBookings(array $driverIds, array $riderIds, array $itemIds, $now): array
    {
        if (!Schema::hasTable('bookings') || empty($driverIds) || empty($riderIds) || empty($itemIds)) {
            return [];
        }

        $statuses = ['Accepted', 'Ongoing', 'Completed', 'Cancelled', 'Rejected'];
        $payments = [
            ['method' => 'cash', 'status' => 'paid'],
            ['method' => 'card', 'status' => 'paid'],
            ['method' => 'wallet', 'status' => 'paid'],
            ['method' => 'paypal', 'status' => 'pending'],
            ['method' => 'cash', 'status' => 'notpaid'],
        ];

        $ids = [];
        foreach ($statuses as $i => $status) {
            $token = 'BKD' . str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT);
            $driverId = $driverIds[$i % count($driverIds)];
            $riderId = $riderIds[$i % count($riderIds)];
            $itemId = $itemIds[$i % count($itemIds)];
            $payment = $payments[$i];

            $rideDate = now()->subDays(4 - $i)->toDateString();
            $checkOut = now()->subDays(3 - $i)->toDateString();
            $basePrice = 280 + ($i * 40);
            $service = 35;
            $total = $basePrice + $service;

            $raw = [
                'token' => $token,
                'itemid' => (string) $itemId,
                'userid' => (string) $riderId,
                'host_id' => $driverId,
                'ride_date' => $rideDate,
                'check_in' => $rideDate,
                'check_out' => $checkOut,
                'start_time' => '10:00',
                'end_time' => '12:00',
                'status' => $status,
                'total_night' => 1,
                'per_night' => $basePrice,
                'price_per_km' => 18,
                'base_price' => $basePrice,
                'cleaning_charge' => 0,
                'guest_charge' => 0,
                'service_charge' => $service,
                'security_money' => 0,
                'iva_tax' => 0,
                'coupon_code' => null,
                'coupon_discount' => 0,
                'discount_price' => 0,
                'total_guest' => 1,
                'amount_to_pay' => $total,
                'total' => $total,
                'admin_commission' => 40,
                'vendor_commission' => 20,
                'vendor_commission_given' => 0,
                'currency_code' => 'INR',
                'cancellation_reasion' => null,
                'cancelled_charge' => 0,
                'transaction' => 'TXN-DEMO-' . ($i + 1),
                'payment_method' => $payment['method'],
                'payment_status' => $payment['status'],
                'item_title' => 'Demo Cab',
                'wall_amt' => 0,
                'note' => null,
                'rating' => $status === 'Completed' ? 5 : 0,
                'module' => 2,
                'cancelled_by' => $status === 'Cancelled' ? 'Guest' : null,
                'deductedAmount' => 0,
                'refundableAmount' => 0,
                'created_at' => $now->copy()->subDays(6 - $i),
                'updated_at' => $now,
            ];

            $row = $this->filterColumns('bookings', $raw);
            DB::table('bookings')->updateOrInsert(['token' => $token], $row);
            $ids[] = (int) DB::table('bookings')->where('token', $token)->value('id');
        }

        return array_values(array_filter($ids));
    }

    private function seedBookingExtensions(array $bookingIds, $now): void
    {
        if (!Schema::hasTable('booking_extensions') || empty($bookingIds)) {
            return;
        }

        foreach ($bookingIds as $idx => $bookingId) {
            $rideId = 'RIDE-' . str_pad((string) ($idx + 1), 4, '0', STR_PAD_LEFT);

            $row = $this->filterColumns('booking_extensions', [
                'booking_id' => $bookingId,
                'ride_id' => $rideId,
                'pickup_location' => json_encode(['address' => 'Pickup Point ' . ($idx + 1)], JSON_UNESCAPED_UNICODE),
                'dropoff_location' => json_encode(['address' => 'Drop Point ' . ($idx + 1)], JSON_UNESCAPED_UNICODE),
                'estimated_distance_km' => 8 + $idx,
                'estimated_duration_min' => 22 + ($idx * 4),
                'pick_otp' => (string) random_int(1000, 9999),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('booking_extensions')->updateOrInsert(['booking_id' => $bookingId], $row);
        }
    }

    private function seedPayoutMethods($now): void
    {
        if (!Schema::hasTable('payout_method')) {
            return;
        }

        foreach (['upi', 'bank', 'paypal'] as $name) {
            $row = $this->filterColumns('payout_method', [
                'name' => $name,
                'status' => 1,
                'module' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('payout_method')->updateOrInsert(['name' => $name], $row);
        }
    }

    private function seedPayouts(array $driverIds, $now): void
    {
        if (!Schema::hasTable('payouts') || empty($driverIds)) {
            return;
        }

        $statuses = ['Pending', 'Success', 'Rejected'];
        foreach ($statuses as $idx => $status) {
            $vendorId = $driverIds[$idx % count($driverIds)];
            $row = $this->filterColumns('payouts', [
                'vendorid' => $vendorId,
                'amount' => 1250 + ($idx * 300),
                'currency' => 'INR',
                'vendor_name' => 'Driver ' . ($idx + 1),
                'request_by' => 'vendor',
                'payment_method' => $idx === 0 ? 'UPI' : 'Bank',
                'account_number' => 'ACCT' . (1000 + $idx),
                'payout_status' => $status,
                'note' => 'Demo payout request',
                'module' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('payouts')->updateOrInsert(
                [
                    'vendorid' => $vendorId,
                    'payout_status' => $status,
                    'request_by' => 'vendor',
                ],
                $row
            );
        }
    }

    private function seedCoupons($now): void
    {
        if (!Schema::hasTable('add_coupons')) {
            return;
        }

        $coupons = [
            ['coupon_title' => 'WELCOME50', 'coupon_code' => 'WELCOME50', 'coupon_value' => 50, 'coupon_type' => 'flat', 'status' => '1'],
            ['coupon_title' => 'RIDE10', 'coupon_code' => 'RIDE10', 'coupon_value' => 10, 'coupon_type' => 'percent', 'status' => '1'],
            ['coupon_title' => 'NIGHT30', 'coupon_code' => 'NIGHT30', 'coupon_value' => 30, 'coupon_type' => 'flat', 'status' => '0'],
        ];

        foreach ($coupons as $coupon) {
            $row = $this->filterColumns('add_coupons', array_merge($coupon, [
                'coupon_subtitle' => 'Demo seeded offer',
                'coupon_expiry_date' => now()->addMonths(2)->toDateString(),
                'min_order_amount' => 200,
                'coupon_description' => 'Auto-generated for QA walkthrough',
                'module' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ]));

            DB::table('add_coupons')->updateOrInsert(
                ['coupon_code' => $coupon['coupon_code']],
                $row
            );
        }
    }

    private function seedSupportTickets(array $riderIds, $now): void
    {
        if (!Schema::hasTable('support_tickets') || empty($riderIds)) {
            return;
        }

        foreach ([1, 2, 3] as $idx) {
            $row = $this->filterColumns('support_tickets', [
                'user_id' => $riderIds[$idx % count($riderIds)],
                'thread_id' => 'T' . (100 + $idx),
                'thread_status' => 1,
                'title' => 'Demo Ticket ' . $idx,
                'description' => 'This is sample support conversation starter #' . $idx,
                'module' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('support_tickets')->updateOrInsert(
                ['thread_id' => 'T' . (100 + $idx)],
                $row
            );
        }
    }

    private function seedHireBookings(array $driverIds, array $riderIds, array $itemIds, $now): void
    {
        if (!Schema::hasTable('hire_bookings') || empty($driverIds) || empty($riderIds)) {
            return;
        }

        $statuses = ['booked', 'ongoing', 'completed', 'cancelled'];
        foreach ($statuses as $idx => $status) {
            $start = $now->copy()->subHours(8 * ($idx + 1));
            $end = $start->copy()->addHours(4);
            $row = $this->filterColumns('hire_bookings', [
                'client_request_id' => 'HIRE-DEMO-' . ($idx + 1),
                'user_id' => $riderIds[$idx % count($riderIds)],
                'driver_id' => $driverIds[$idx % count($driverIds)],
                'item_id' => $itemIds[$idx % max(count($itemIds), 1)] ?? null,
                'duration_hours' => 4,
                'start_at' => $start,
                'end_at' => $end,
                'amount_to_pay' => 1800 + ($idx * 200),
                'currency_code' => 'INR',
                'payment_method' => $idx % 2 === 0 ? 'cash' : 'card',
                'payment_status' => $status === 'completed' ? 'paid' : 'pending',
                'status' => $status,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('hire_bookings')->updateOrInsert(
                ['client_request_id' => 'HIRE-DEMO-' . ($idx + 1)],
                $row
            );
        }
    }

    private function resolvePackageId($now): ?int
    {
        if (!Schema::hasTable('all_packages')) {
            return null;
        }

        $query = DB::table('all_packages');
        if (Schema::hasColumn('all_packages', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $existingId = $query->orderBy('id')->value('id');
        if (!is_null($existingId)) {
            return (int) $existingId;
        }

        $packageRow = $this->filterColumns('all_packages', [
            'package_name' => 'Demo Starter Plan',
            'package_total_day' => 30,
            'package_price' => 999,
            'package_description' => 'Auto-created demo package for seeded users',
            'max_item' => 10,
            'status' => '1',
            'module' => 2,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('all_packages')->insert($packageRow);

        $newQuery = DB::table('all_packages');
        if (Schema::hasColumn('all_packages', 'deleted_at')) {
            $newQuery->whereNull('deleted_at');
        }

        $newId = $newQuery->orderByDesc('id')->value('id');

        return is_null($newId) ? null : (int) $newId;
    }

    private function filterColumns(string $table, array $data): array
    {
        $columns = Schema::getColumnListing($table);

        return Arr::only($data, $columns);
    }
}
