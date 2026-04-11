<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVendorWalletTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vendor_wallet', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->integer('vendor_id'); // Vendor ID
            $table->unsignedBigInteger('booking_id')->nullable()->default(0); // Booking ID
            $table->unsignedBigInteger('payout_id')->nullable()->default(0); // Payout ID
            $table->decimal('amount', 15, 2); // Amount with precision and scale
            $table->enum('type', ['credit', 'debit', 'refund']); // Type of transaction
            $table->text('description')->nullable(); // Description of the transaction
            $table->timestamps(); // created_at and updated_at timestamps
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vendor_wallet');
    }
}
