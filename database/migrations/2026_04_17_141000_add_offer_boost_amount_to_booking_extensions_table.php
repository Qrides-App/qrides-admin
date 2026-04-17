<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('booking_extensions', function (Blueprint $table) {
            if (! Schema::hasColumn('booking_extensions', 'offer_boost_amount')) {
                $table->decimal('offer_boost_amount', 10, 2)->default(0)->after('ride_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_extensions', function (Blueprint $table) {
            if (Schema::hasColumn('booking_extensions', 'offer_boost_amount')) {
                $table->dropColumn('offer_boost_amount');
            }
        });
    }
};
