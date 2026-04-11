<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSupportTicketsTable extends Migration
{
    public function up()
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id(); // int(11) UNSIGNED AUTO_INCREMENT
            $table->unsignedBigInteger('user_id'); // int(11) UNSIGNED
            $table->string('title'); // varchar(255)
            $table->text('description'); // text
            $table->string('thread_id')->nullable(); // varchar(5) with NULL default
            $table->integer('thread_status')->default(1); // int(11) with default 1
            $table->tinyInteger('module')->default(2); // tinyint(1) with default 2
            $table->timestamps(); // created_at and updated_at timestamps
        });
    }

    public function down()
    {
        Schema::dropIfExists('support_tickets');
    }
}
