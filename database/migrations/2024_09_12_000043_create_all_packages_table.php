<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAllPackagesTable extends Migration
{
    public function up()
    {
        Schema::create('all_packages', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->string('package_name'); // Name of the package
            $table->integer('package_total_day'); // Total days for the package
            $table->decimal('package_price', 15, 2); // Price of the package
            $table->longText('package_description')->nullable(); // Description of the package
            $table->string('status')->default('1'); // Status of the package
            $table->integer('max_item')->nullable(); // Maximum number of items (nullable)
            $table->integer('module')->nullable(); // Additional column not listed in the model but present in the table structure
            $table->timestamps(); // created_at and updated_at timestamps
            $table->softDeletes(); // deleted_at timestamp for soft deletes
        });
    }

    public function down()
    {
        Schema::dropIfExists('all_packages');
    }
}
