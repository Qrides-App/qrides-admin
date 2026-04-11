<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRentalItemRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rental_item_rules', function (Blueprint $table) {
            $table->id(); // auto-incrementing primary key
            $table->string('rule_name')->nullable(); // varchar(255)
            $table->integer('module')->default(1); // int(11)
            $table->boolean('status')->default(1); // tinyint(1)
            $table->timestamps(); // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rental_item_rules');
    }
}
