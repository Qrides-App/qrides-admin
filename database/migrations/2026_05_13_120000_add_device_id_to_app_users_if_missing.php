<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('app_users') || Schema::hasColumn('app_users', 'device_id')) {
            return;
        }

        Schema::table('app_users', function (Blueprint $table) {
            $table->text('device_id')->nullable()->after('fcm');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('app_users') || ! Schema::hasColumn('app_users', 'device_id')) {
            return;
        }

        Schema::table('app_users', function (Blueprint $table) {
            $table->dropColumn('device_id');
        });
    }
};
