<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateModuleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('module', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->string('name'); // Name of the module
            $table->text('description')->nullable(); // Description of the module
            $table->tinyInteger('status')->default(0); // Status of the module (tinyint(4))
            $table->tinyInteger('default_module')->default(0); // Default module flag (tinyint(4))
            $table->timestamps(); // created_at and updated_at timestamps
            $table->softDeletes(); // deleted_at datetime for soft deletes
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('module');
    }
}
