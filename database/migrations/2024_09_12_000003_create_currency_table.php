<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCurrencyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('currency', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('currency_name', 50); // Currency name (e.g., US Dollar), NOT NULL
            $table->string('currency_code', 10)->unique(); // Currency code (e.g., USD), NOT NULL
            $table->double('value_against_default_currency')->nullable(); // Exchange rate against default currency, nullable
            $table->string('currency_symbol', 50); // Currency symbol (e.g., $), NOT NULL
            $table->tinyInteger('status')->default(0); // '0' => Inactive, '1' => Active

            // Timestamps with ON UPDATE CURRENT_TIMESTAMP for updated_at
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('currency');
    }
}
