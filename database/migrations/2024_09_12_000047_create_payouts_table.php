<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayoutsTable extends Migration
{
    public function up()
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id(); // bigint(20) UNSIGNED AUTO_INCREMENT
            $table->integer('vendorid'); // int(11)
            $table->decimal('amount', 15, 2); // decimal(15,2)
            $table->string('currency')->nullable(); // varchar(255)
            $table->string('vendor_name')->nullable(); // varchar(255)
            $table->string('payment_method')->nullable(); // varchar(255)
            $table->string('account_number')->nullable(); // varchar(255)
            $table->enum('payout_status', ['Pending', 'Success'])->nullable(); // enum('Pending', 'Success')
            $table->text('booking_Ids')->nullable(); // text
            $table->tinyInteger('module')->default(1); // tinyint(1)
            $table->timestamps(); // created_at and updated_at timestamps
            $table->softDeletes(); // deleted_at timestamp for soft deletes
        });
    }

    public function down()
    {
        Schema::dropIfExists('payouts');
    }
}
