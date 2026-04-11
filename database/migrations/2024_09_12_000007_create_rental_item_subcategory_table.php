<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRentalItemSubcategoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rental_item_subcategory', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->unsignedBigInteger('make_id')->nullable(); // Foreign key to the 'vehicle_makes' table, nullable
            $table->string('name', 50); // Name of the subcategory with a length of 50 characters
            $table->string('description', 255)->nullable(); // Description of the subcategory, with a length of 255 characters
            $table->tinyInteger('status'); // Status (e.g., active, inactive) as tinyint
            $table->tinyInteger('module')->default(1); // Module info with a default value of 1
            $table->timestamps(); // Adds created_at and updated_at columns
            $table->softDeletes(); // Adds deleted_at column for soft deletes

            // Foreign key constraint for the make_id
            $table->foreign('make_id')->references('id')->on('vehicle_makes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rental_item_subcategory');
    }
}
