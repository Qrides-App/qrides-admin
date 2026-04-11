<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRentalItemWishlistsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rental_item_wishlists', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->integer('user_id'); // User who added the item to the wishlist
            $table->integer('item_id'); // Foreign key to the 'items' table
            $table->integer('module')->nullable(); // Additional module info, if needed

            // Foreign key constraint for the item
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');

            // Foreign key constraint for the user (assuming a users table exists)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->timestamps(); // Adds created_at and updated_at columns
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rental_item_wishlists');
    }
}
