<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReviewsTable extends Migration
{
    public function up()
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id(); // bigint(20) UNSIGNED AUTO_INCREMENT
            $table->integer('bookingid'); // int(11)
            $table->integer('item_id')->default(0); // int(11) with default 0
            $table->longText('item_name')->nullable(); // longtext
            $table->string('guestid'); // varchar(255)
            $table->string('guest_name')->nullable(); // varchar(255)
            $table->integer('hostid')->nullable(); // int(11)
            $table->string('host_name')->nullable(); // varchar(255)
            $table->integer('guest_rating')->default(0); // int(11) with default 0
            $table->longText('guest_message')->nullable(); // longtext
            $table->integer('host_rating')->default(0); // int(11) with default 0
            $table->longText('host_message')->nullable(); // longtext
            $table->integer('module')->default(1); // int(11) with default 1
            $table->timestamps(); // created_at and updated_at timestamps
            $table->softDeletes(); // deleted_at timestamp
        });
    }

    public function down()
    {
        Schema::dropIfExists('reviews');
    }
}
