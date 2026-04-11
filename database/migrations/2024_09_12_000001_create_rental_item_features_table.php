<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRentalItemFeaturesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rental_item_features', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('name'); // Not nullable according to table structure
            $table->string('description')->nullable(); // Change to string
            $table->string('status')->default('1'); // Change to string, default '1'
            $table->tinyInteger('module')->default(1); // Change to tinyInteger with default value
            $table->string('type')->nullable();

            // Soft Deletes and Timestamps
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rental_item_features');
    }
}
