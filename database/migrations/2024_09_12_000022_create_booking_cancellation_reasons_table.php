<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingCancellationReasonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('booking_cancellation_reasons', function (Blueprint $table) {
            $table->id('order_cancellation_id'); // Primary key
            $table->string('reason'); // Reason for cancellation
            $table->string('user_type'); // Type of user (e.g., customer, admin)
            $table->tinyInteger('status')->default(1); // Status of the cancellation reason
            $table->tinyInteger('module')->default(1); // Module field (if needed)
            $table->timestamps(); // created_at and updated_at timestamps
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('booking_cancellation_reasons');
    }
}
