<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmailNotificationMappingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('email_notification_mappings', function (Blueprint $table) {
            $table->unsignedInteger('email_type_id'); // int(10) UNSIGNED, not nullable
            $table->unsignedInteger('email_sms_notification_id'); // int(10) UNSIGNED, not nullable
            $table->unsignedInteger('module'); // int(10) UNSIGNED, not nullable

            // Define composite primary key
            $table->primary(['email_type_id', 'email_sms_notification_id', 'module']);

            // Optionally, you might want to add foreign keys if needed
            // $table->foreign('email_type_id')->references('id')->on('email_types')->onDelete('cascade');
            // $table->foreign('email_sms_notification_id')->references('id')->on('email_sms_notification')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('email_notification_mappings');
    }
}
