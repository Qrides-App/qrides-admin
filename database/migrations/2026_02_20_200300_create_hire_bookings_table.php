<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hire_bookings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('driver_id');
            $table->unsignedBigInteger('item_id')->nullable();
            $table->unsignedInteger('duration_hours');
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->decimal('amount_to_pay', 12, 2)->default(0);
            $table->string('currency_code', 10)->default('INR');
            $table->string('payment_method', 30)->nullable();
            $table->string('payment_status', 30)->default('pending');
            $table->string('status', 30)->default('booked');
            $table->timestamps();

            $table->index(['driver_id', 'status'], 'hire_bookings_driver_status_idx');
            $table->index(['user_id', 'status'], 'hire_bookings_user_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hire_bookings');
    }
};
