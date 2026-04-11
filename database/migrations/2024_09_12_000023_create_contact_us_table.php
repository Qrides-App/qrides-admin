<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContactUsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contact_us', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->string('tittle'); // Title of the contact message
            $table->string('description'); // Description of the contact message
            $table->unsignedBigInteger('user'); // Foreign key to 'app_users' table
            $table->integer('status'); // Status of the contact message
            $table->tinyInteger('module')->default(1); // Optional module field
            $table->timestamps(); // created_at and updated_at timestamps

            // Foreign key constraint for the user
            $table->foreign('user')->references('id')->on('app_users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contact_us');
    }
}
