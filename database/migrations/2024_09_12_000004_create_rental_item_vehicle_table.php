<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRentalItemVehicleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rental_item_vehicle', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->unsignedBigInteger('item_id'); // Foreign key to the 'items' table
            $table->string('year', 255)->nullable(); // Vehicle year as varchar(255)
            $table->string('odometer', 50)->nullable(); // Odometer as varchar(50)
            $table->string('transmission', 50)->nullable(); // Transmission type (e.g., Manual, Automatic)

            // Foreign key constraint
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
        Schema::dropIfExists('rental_item_vehicle');
    }
}
