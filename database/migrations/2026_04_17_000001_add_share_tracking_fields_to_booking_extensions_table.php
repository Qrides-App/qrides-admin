<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_extensions', function (Blueprint $table) {
            $table->string('share_token', 80)->nullable()->unique()->after('ride_id');
            $table->boolean('share_tracking_enabled')->default(false)->after('share_token');
            $table->timestamp('share_token_expires_at')->nullable()->after('share_tracking_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('booking_extensions', function (Blueprint $table) {
            $table->dropColumn(['share_token_expires_at', 'share_tracking_enabled', 'share_token']);
        });
    }
};
