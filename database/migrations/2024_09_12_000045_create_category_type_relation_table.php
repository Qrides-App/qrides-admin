<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoryTypeRelationTable extends Migration
{
    public function up()
    {
        Schema::create('category_type_relation', function (Blueprint $table) {
            $table->id(); // This will be int(11) auto-increment by default
            $table->integer('category_id'); // int(11)
            $table->unsignedBigInteger('type_id'); // bigint(20) UNSIGNED
            $table->foreign('category_id')->references('id')->on('vehicle_makes')->onDelete('cascade');
            $table->foreign('type_id')->references('id')->on('item_types')->onDelete('cascade');

            // Optional: Add indexes if needed
            $table->index('category_id');
            $table->index('type_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('category_type_relation');
    }
}
