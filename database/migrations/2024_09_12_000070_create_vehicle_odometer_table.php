<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVehicleOdometerTable extends Migration
{
    public function up()
    {
        Schema::create('vehicle_odometer', function (Blueprint $table) {
            $table->id(); // int(11) UNSIGNED AUTO_INCREMENT
            $table->string('name'); // varchar(255)
            $table->tinyInteger('status')->default(1); // tinyint(1) with default 1 (assuming Active is represented by 1)
            $table->integer('module')->nullable(); // int(11) with NULL allowed
            $table->timestamps(); // created_at and updated_at with current_timestamp() and default values
            $table->softDeletes(); // deleted_at timestamp
        });
    }

    public function down()
    {
        Schema::dropIfExists('vehicle_odometer');
    }
}
