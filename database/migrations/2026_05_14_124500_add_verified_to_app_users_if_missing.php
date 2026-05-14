<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('app_users') || Schema::hasColumn('app_users', 'verified')) {
            return;
        }

        Schema::table('app_users', function (Blueprint $table) {
            $table->boolean('verified')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('app_users') || ! Schema::hasColumn('app_users', 'verified')) {
            return;
        }

        Schema::table('app_users', function (Blueprint $table) {
            $table->dropColumn('verified');
        });
    }
};
