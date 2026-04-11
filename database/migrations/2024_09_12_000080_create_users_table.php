<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // bigint(20) UNSIGNED AUTO_INCREMENT
            $table->string('name'); // varchar(255)
            $table->string('email')->unique(); // varchar(255) with unique index
            $table->dateTime('email_verified_at')->nullable(); // datetime
            $table->string('password'); // varchar(255)
            $table->rememberToken(); // varchar(255)
            $table->timestamps(); // created_at and updated_at with current_timestamp()
            $table->softDeletes(); // deleted_at timestamp
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
}
