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
        if (! Schema::hasTable('app_users')) {
            return;
        }

        Schema::table('app_users', function (Blueprint $table) {
            if (! Schema::hasColumn('app_users', 'token')) {
                $table->text('token')->nullable()->after('password');
            }
            if (! Schema::hasColumn('app_users', 'reset_token')) {
                $table->integer('reset_token')->nullable()->default(0)->after('token');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('app_users')) {
            return;
        }

        Schema::table('app_users', function (Blueprint $table) {
            if (Schema::hasColumn('app_users', 'reset_token')) {
                $table->dropColumn('reset_token');
            }
            if (Schema::hasColumn('app_users', 'token')) {
                $table->dropColumn('token');
            }
        });
    }
};
