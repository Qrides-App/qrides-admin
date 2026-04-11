<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppUsersTable extends Migration
{
    public function up()
    {
        Schema::create('app_users', function (Blueprint $table) {
            $table->id(); // bigint(20) UNSIGNED AUTO_INCREMENT
            $table->string('first_name'); // varchar(255)
            $table->string('middle')->nullable(); // varchar(255)
            $table->string('last_name')->nullable(); // varchar(255)
            $table->string('email')->unique(); // varchar(255) with unique index
            $table->string('phone')->nullable(); // varchar(255)
            $table->string('phone_country')->nullable(); // varchar(255)
            $table->string('default_country')->nullable(); // varchar(255)
            $table->text('intro')->nullable(); // text
            $table->string('language')->nullable(); // varchar(250)
            $table->string('country')->nullable(); // varchar(250)
            $table->string('password'); // varchar(255)
            $table->decimal('wallet', 15, 2)->nullable(); // decimal(15,2) with NULL allowed
            $table->integer('otp_value')->default(0); // int(11) with default 0
            $table->text('token')->nullable(); // text
            $table->integer('reset_token')->default(0); // int(11) with default 0
            $table->tinyInteger('verified')->default(0); // tinyint(4) with default 0
            $table->tinyInteger('phone_verify')->default(0); // tinyint(4) with default 0
            $table->tinyInteger('email_verify')->default(0); // tinyint(4) with default 0
            $table->string('login_type')->nullable(); // varchar(250)
            $table->enum('host_status', ['0', '1', '2', ''])->default('0'); // enum with default '0'
            $table->date('birthdate')->nullable(); // date
            $table->string('social_id')->nullable(); // varchar(250)
            $table->decimal('ave_host_rate', 15, 2)->default(0.00); // decimal(15,2) with default 0.00
            $table->decimal('avr_guest_rate', 15, 2)->default(0.00); // decimal(15,2) with default 0.00
            $table->tinyInteger('status')->default(1); // tinyint(1) with default 1
            $table->unsignedBigInteger('package_id')->nullable()->index(); // bigint(20) UNSIGNED with index
            $table->text('fcm')->nullable(); // text
            $table->tinyInteger('sms_notification')->default(0); // tinyint(4) with default 0
            $table->tinyInteger('email_notification')->default(0); // tinyint(4) with default 0
            $table->tinyInteger('push_notification')->default(0); // tinyint(4) with default 0
            $table->text('device_id')->nullable(); // text
            $table->timestamps(); // created_at and updated_at timestamps
            $table->softDeletes(); // deleted_at timestamp
        });
    }

    public function down()
    {
        Schema::dropIfExists('app_users');
    }
}
