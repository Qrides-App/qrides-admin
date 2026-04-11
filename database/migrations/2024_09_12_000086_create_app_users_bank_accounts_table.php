<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppUsersBankAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_users_bank_accounts', function (Blueprint $table) {
            $table->id(); // auto-incrementing primary key
            $table->unsignedBigInteger('user_id'); // foreign key to the AppUser model
            $table->string('account_name'); // varchar(255)
            $table->string('bank_name'); // varchar(255)
            $table->string('branch_name')->nullable(); // varchar(255), nullable
            $table->string('account_number'); // varchar(255)
            $table->string('iban')->nullable(); // varchar(255), nullable
            $table->string('swift_code')->nullable(); // varchar(255), nullable
            $table->timestamps(); // created_at and updated_at

            // Optional: Add a foreign key constraint if AppUser is an existing model
            $table->foreign('user_id')->references('id')->on('app_users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_users_bank_accounts');
    }
}
