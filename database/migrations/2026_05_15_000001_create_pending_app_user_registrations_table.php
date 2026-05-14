<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pending_app_user_registrations')) {
            return;
        }

        Schema::create('pending_app_user_registrations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->index();
            $table->string('phone');
            $table->string('phone_country', 12);
            $table->string('default_country', 8)->nullable();
            $table->string('user_type', 20)->default('user');
            $table->string('fcm')->nullable();
            $table->string('device_id')->nullable();
            $table->string('token', 191)->nullable();
            $table->string('otp_channel', 40)->nullable();
            $table->timestamp('otp_sent_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['phone', 'phone_country'], 'pending_app_users_phone_country_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_app_user_registrations');
    }
};
