<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmailSmsNotificationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('email_sms_notification', function (Blueprint $table) {
            $table->id(); // auto-incrementing primary key with UNSIGNED integer (default)
            $table->string('temp_name', 250); // varchar(250), not nullable
            $table->tinyInteger('module'); // tinyint(4), not nullable
            $table->string('role', 250); // varchar(250), not nullable
            $table->string('subject', 191); // varchar(191), not nullable
            $table->text('body'); // text, not nullable
            $table->string('link_text', 191)->nullable(); // varchar(191), nullable
            $table->string('lang', 10); // varchar(10), not nullable
            $table->integer('lang_id')->nullable()->default(0); // int(11), nullable with default 0
            $table->text('sms'); // text, not nullable
            $table->text('push_notification'); // text, not nullable
            $table->tinyInteger('emailsent')->nullable()->default(1); // tinyint(1), nullable with default 1
            $table->tinyInteger('smssent')->default(1); // tinyint(1), default 1
            $table->tinyInteger('pushsent')->default(1); // tinyint(1), default 1
            $table->string('vendorsubject', 91)->nullable(); // varchar(91), nullable
            $table->text('vendorbody')->nullable(); // text, nullable
            $table->text('vendorpush_notification')->nullable(); // text, nullable
            $table->tinyInteger('vendoremailsent')->default(0); // tinyint(4), default 0
            $table->tinyInteger('vendorsmssent')->default(0); // tinyint(4), default 0
            $table->tinyInteger('vendorpushsent')->default(0); // tinyint(4), default 0
            $table->text('vendorsms')->nullable(); // text, nullable
            $table->string('adminsubject', 99)->nullable(); // varchar(99), nullable
            $table->text('adminbody')->nullable(); // text, nullable
            $table->text('adminpush_notification')->nullable(); // text, nullable
            $table->tinyInteger('adminemailsent')->default(0); // tinyint(4), default 0
            $table->tinyInteger('adminsmssent')->default(0); // tinyint(4), default 0
            $table->tinyInteger('adminpushsent')->default(0); // tinyint(4), default 0
            $table->text('adminsms')->nullable(); // text, nullable
            $table->tinyInteger('status')->default(1); // tinyint(1), default 1
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
        Schema::dropIfExists('email_sms_notification');
    }
}
