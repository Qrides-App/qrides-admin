<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSlidersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sliders', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->string('heading'); // Heading for the slider
            $table->string('subheading')->nullable(); // Subheading for the slider (nullable)
            $table->string('status')->nullable(); // Status of the slider (nullable)
            $table->tinyInteger('module')->default(1); // Module associated with the slider (default 1)
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
        Schema::dropIfExists('sliders');
    }
}
