<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWalletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id(); // Creates an auto-incrementing unsigned BIGINT (primary key)
            $table->integer('user_id'); // Integer column for user ID
            $table->decimal('amount', 15, 2); // Decimal column for amount with 15 digits and 2 decimal places
            $table->enum('type', ['credit', 'debit']); // Enum column for type with possible values 'credit' and 'debit'
            $table->text('description'); // Text column for description
            $table->tinyInteger('status'); // TinyInteger column for status
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP')); // Timestamp column for created_at with default value
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')); // Timestamp column for updated_at with default value and auto-update on change
            $table->string('currency')->nullable(); // String column for currency (nullable)

            // Indexes, Foreign Keys, or other constraints can be added here
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wallets');
    }
}
