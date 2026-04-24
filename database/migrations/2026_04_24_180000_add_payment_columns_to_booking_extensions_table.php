<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_extensions', function (Blueprint $table) {
            if (! Schema::hasColumn('booking_extensions', 'captain_payment_mode')) {
                $table->string('captain_payment_mode')->nullable()->after('ride_id');
            }
            if (! Schema::hasColumn('booking_extensions', 'captain_payment_reference')) {
                $table->string('captain_payment_reference')->nullable()->after('captain_payment_mode');
            }
            if (! Schema::hasColumn('booking_extensions', 'payment_collection_note')) {
                $table->text('payment_collection_note')->nullable()->after('captain_payment_reference');
            }
            if (! Schema::hasColumn('booking_extensions', 'app_payment_request_token')) {
                $table->string('app_payment_request_token', 64)->nullable()->after('payment_collection_note');
            }
            if (! Schema::hasColumn('booking_extensions', 'app_payment_request_url')) {
                $table->text('app_payment_request_url')->nullable()->after('app_payment_request_token');
            }
            if (! Schema::hasColumn('booking_extensions', 'app_payment_request_expires_at')) {
                $table->timestamp('app_payment_request_expires_at')->nullable()->after('app_payment_request_url');
            }
            if (! Schema::hasColumn('booking_extensions', 'payment_collected_at')) {
                $table->timestamp('payment_collected_at')->nullable()->after('app_payment_request_expires_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('booking_extensions', function (Blueprint $table) {
            foreach ([
                'captain_payment_mode',
                'captain_payment_reference',
                'payment_collection_note',
                'app_payment_request_token',
                'app_payment_request_url',
                'app_payment_request_expires_at',
                'payment_collected_at',
            ] as $column) {
                if (Schema::hasColumn('booking_extensions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
