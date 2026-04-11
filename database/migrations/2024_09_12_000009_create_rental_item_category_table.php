<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRentalItemCategoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rental_item_category', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('name', 50); // Name of the category with a length of 50 characters
            $table->integer('module')->default(1); // Module info with a default value of 1
            $table->string('description', 255); // Description of the category with a length of 255 characters
            $table->tinyInteger('status')->default(0); // Status of the category (Active/Inactive) as tinyint with default value of 0
            $table->timestamps(); // Adds created_at and updated_at columns
            $table->softDeletes(); // Adds deleted_at column for soft deletes

            // Index for status
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rental_item_category');
    }
}
