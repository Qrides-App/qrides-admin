<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppUserOtpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_user_otps', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('phone', 15); // User's phone number, varchar(15)
            $table->string('country_code', 5); // Country code for the phone number, varchar(5)
            $table->string('otp_code', 10); // OTP code, varchar(10)
            $table->timestamp('created_at')->useCurrent()->nullable(); // Timestamp for creation, nullable with default current_timestamp()
            $table->timestamp('updated_at')->nullable(); // Timestamp for last update, nullable
            $table->timestamp('expires_at')->default(DB::raw('0000-00-00 00:00:00')); // Timestamp for expiration with default value

            // Indexes
            $table->index('phone');
            $table->index('otp_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_user_otps');
    }
}
