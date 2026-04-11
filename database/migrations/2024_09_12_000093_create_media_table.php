<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMediaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->string('model_type'); // Model type
            $table->unsignedBigInteger('model_id'); // Model ID, unsigned big integer
            $table->char('uuid', 36)->nullable(); // UUID, nullable
            $table->string('collection_name'); // Collection name
            $table->string('name'); // Name of the media
            $table->string('file_name'); // File name
            $table->string('mime_type')->nullable(); // MIME type, nullable
            $table->string('disk'); // Disk name
            $table->string('conversions_disk')->nullable(); // Conversions disk, nullable
            $table->unsignedBigInteger('size'); // Size of the file, unsigned big integer
            $table->longText('manipulations'); // Manipulations
            $table->longText('custom_properties'); // Custom properties
            $table->longText('generated_conversions'); // Generated conversions
            $table->longText('responsive_images'); // Responsive images
            $table->unsignedInteger('order_column')->nullable(); // Order column, nullable
            $table->timestamps(); // created_at and updated_at timestamps
        });

        // Adding indexes after column definitions
        Schema::table('media', function (Blueprint $table) {
            $table->index(['model_type', 'model_id']); // Composite index for model_type and model_id
            $table->index('uuid'); // Index on uuid
            $table->index('order_column'); // Index on order_column
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('media');
    }
}
