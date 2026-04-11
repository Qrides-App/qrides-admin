<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id(); // auto-incrementing primary key (bigint UNSIGNED)
            $table->string('city_name', 255); // varchar(255), not nullable
            $table->string('country_code', 20); // varchar(20), not nullable
            $table->longText('description')->nullable(); // longtext, nullable
            $table->string('status', 255)->nullable(); // varchar(255), nullable
            $table->string('latitude', 20)->nullable(); // varchar(20), nullable
            $table->string('longtitude', 20); // varchar(20), not nullable
            $table->string('region', 250)->nullable(); // varchar(250), nullable
            $table->tinyInteger('module')->default(1); // tinyint(1), default 1
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
        Schema::dropIfExists('cities');
    }
}
