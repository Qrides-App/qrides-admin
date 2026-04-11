<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRentalItemMetaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rental_item_meta', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->unsignedBigInteger('rental_item_id'); // Foreign key to the rental items table
            $table->string('meta_key', 255); // Key for the meta value with a length of 255 characters
            $table->longText('meta_value')->nullable(); // Value for the meta key, allows NULL
            $table->timestamps(); // Adds created_at and updated_at columns

            // Foreign key constraint for the rental_item_id
            $table->foreign('rental_item_id')->references('id')->on('rental_items')->onDelete('cascade');

            // Index on rental_item_id and meta_key for quick lookups
            $table->index(['rental_item_id', 'meta_key']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rental_item_meta');
    }
}
