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
        Schema::create('rental_item_dates', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('item_id');
            $table->enum('status', ['Available', 'Not available'])->default('Available');
            $table->unsignedBigInteger('booking_id')->default(0);
            $table->decimal('price', 10)->default(0);
            $table->tinyInteger('min_stay')->default(0);
            $table->integer('min_day')->default(0);
            $table->integer('range_index')->default(0);
            $table->date('date')->nullable();
            $table->time('time_slot')->nullable();
            $table->enum('type', ['calendar', 'normal', 'slot'])->default('normal');
            $table->tinyInteger('module')->default(1);
            $table->integer('additional_hour')->nullable();
            $table->timestamps();

            $table->unique(['item_id', 'date'], 'property_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_item_dates');
    }
};
