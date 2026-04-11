<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRentalItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rental_items', function (Blueprint $table) {
            $table->bigIncrements('id'); // big integer as per table structure
            $table->string('token', 191)->nullable();
            $table->string('title', 255);
            $table->longText('description')->nullable();
            $table->text('full_text_search')->nullable();
            $table->double('item_rating', 15, 2)->default(0.00); // updated as per table structure
            $table->string('mobile', 255)->nullable();
            $table->decimal('price', 15, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->longText('address')->nullable();
            $table->string('state_region', 255)->nullable();
            $table->string('city_name', 255)->nullable();
            $table->unsignedInteger('city')->nullable();
            $table->string('country', 250)->nullable();
            $table->string('zip_postal_code', 255)->nullable();
            $table->double('longitude')->nullable();
            $table->double('latitude')->nullable();
            $table->unsignedBigInteger('userid_id')->nullable();
            $table->unsignedBigInteger('item_type_id')->nullable();
            $table->string('features_id', 255)->nullable(); // varchar, not foreign key as per table structure
            $table->unsignedBigInteger('place_id')->nullable();
            $table->unsignedInteger('booking_policies_id')->nullable();
            $table->unsignedBigInteger('subcategory_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('service_type', 25)->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('module')->default(1);
            $table->longText('steps_completed')->nullable();
            $table->decimal('step_progress', 5, 2)->default(0.00);
            $table->tinyInteger('is_featured')->default(0);
            $table->tinyInteger('is_verified')->default(0);
            $table->tinyInteger('status')->default(0);

            // Soft Deletes and Timestamps
            $table->softDeletes();
            $table->timestamps();

            // Foreign Key Constraints
            $table->foreign('userid_id')->references('id')->on('app_users')->onDelete('cascade');
            $table->foreign('item_type_id')->references('id')->on('item_types')->onDelete('cascade');
            $table->foreign('place_id')->references('id')->on('cities')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('subcategory_id')->references('id')->on('subcategories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rental_items');
    }
}
