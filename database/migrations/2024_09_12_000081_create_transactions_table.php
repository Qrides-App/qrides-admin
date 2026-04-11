<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id(); // bigint(20) UNSIGNED AUTO_INCREMENT
            $table->unsignedBigInteger('booking_id'); // bigint(20) UNSIGNED
            $table->string('transaction_id'); // varchar(255)
            $table->decimal('amount', 15, 2); // decimal(15,2)
            $table->string('payment_status'); // varchar(255)
            $table->string('gateway_name', 50); // varchar(50)
            $table->string('currency_code', 20); // Add this column
            $table->text('response_data')->nullable(); // text
            $table->timestamps(); // created_at and updated_at with current_timestamp()
            $table->softDeletes(); // deleted_at timestamp
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
