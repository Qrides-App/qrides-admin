<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'client_request_id')) {
                $table->string('client_request_id', 64)->nullable()->after('token');
                $table->index('client_request_id', 'bookings_client_request_id_idx');
                $table->unique(['userid', 'client_request_id'], 'bookings_user_client_request_unique');
            }
        });

        Schema::table('hire_bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('hire_bookings', 'client_request_id')) {
                $table->string('client_request_id', 64)->nullable()->after('id');
                $table->index('client_request_id', 'hire_bookings_client_request_id_idx');
                $table->unique(['user_id', 'client_request_id'], 'hire_bookings_user_client_request_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'client_request_id')) {
                $table->dropUnique('bookings_user_client_request_unique');
                $table->dropIndex('bookings_client_request_id_idx');
                $table->dropColumn('client_request_id');
            }
        });

        Schema::table('hire_bookings', function (Blueprint $table) {
            if (Schema::hasColumn('hire_bookings', 'client_request_id')) {
                $table->dropUnique('hire_bookings_user_client_request_unique');
                $table->dropIndex('hire_bookings_client_request_id_idx');
                $table->dropColumn('client_request_id');
            }
        });
    }
};

