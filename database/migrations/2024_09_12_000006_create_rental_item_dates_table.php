<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRentalItemDatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rental_item_dates', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->unsignedBigInteger('item_id'); // Foreign key to the 'items' table
            $table->enum('status', ['Available', 'Not available']); // Status of the item date
            $table->decimal('price', 10, 2); // Price for the item date
            $table->integer('additional_hour')->nullable(); // Additional hour information
            $table->tinyInteger('min_stay')->nullable(); // Minimum stay requirement
            $table->integer('min_day')->nullable(); // Minimum day requirement
            $table->date('date'); // Specific date
            $table->enum('type', ['calendar', 'normal', 'slot']); // Type of date (e.g., special, regular)
            $table->tinyInteger('module')->nullable(); // Module info
            $table->integer('booking_id')->nullable(); // Booking reference ID
            $table->timestamps(); // Adds created_at and updated_at columns

            // Foreign key constraint for the item
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rental_item_dates');
    }
}
