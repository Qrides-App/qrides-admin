<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTestimonialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->string('name'); // Name of the person giving the testimonial
            $table->string('designation'); // Designation of the person giving the testimonial
            $table->longText('description'); // Testimonial text (longtext)
            $table->string('image')->nullable(); // Image associated with the testimonial
            $table->integer('rating'); // Rating associated with the testimonial
            $table->string('status')->nullable(); // Status of the testimonial (nullable)
            $table->timestamps(); // created_at and updated_at timestamps
            $table->softDeletes(); // deleted_at timestamp for soft deletes
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('testimonials');
    }
}
