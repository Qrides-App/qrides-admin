<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppUserMetaTable extends Migration
{
    public function up()
    {
        Schema::create('app_user_meta', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->bigInteger('user_id')->index(); // User ID with index
            $table->string('meta_key'); // Meta key
            $table->longText('meta_value'); // Meta value (longtext)
            $table->timestamps(); // created_at and updated_at timestamps
        });
    }

    public function down()
    {
        Schema::dropIfExists('app_user_meta');
    }
}
