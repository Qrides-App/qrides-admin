<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGeneralSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('general_settings', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->string('meta_key')->unique(); // Unique meta key
            $table->text('meta_value'); // Meta value
            $table->tinyInteger('module')->default(1); // Tinyint module with default value of 1
            $table->timestamps(); // created_at and updated_at timestamps
            $table->softDeletes(); // deleted_at timestamp for soft deletes
        });
    }

    public function down()
    {
        Schema::dropIfExists('general_settings');
    }
}
