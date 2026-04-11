<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingCancellationPoliciesTable extends Migration
{
    public function up()
    {
        Schema::create('booking_cancellation_policies', function (Blueprint $table) {
            $table->id(); // bigint(20) UNSIGNED AUTO_INCREMENT
            $table->string('name'); // varchar(255)
            $table->text('description')->nullable(); // text
            $table->enum('type', ['fixed', 'percent', 'none']); // enum('fixed', 'percent', 'none')
            $table->decimal('value', 15, 2)->nullable(); // decimal(15,2)
            $table->tinyInteger('status'); // tinyint(1)
            $table->tinyInteger('module')->nullable(); // tinyint(1)
            $table->integer('cancellation_time'); // int(10)
            $table->timestamps(); // created_at and updated_at timestamps
        });
    }

    public function down()
    {
        Schema::dropIfExists('booking_cancellation_policies');
    }
}
