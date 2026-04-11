<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id(); // auto-incrementing primary key (bigint UNSIGNED)
            $table->string('token', 10)->nullable()->index(); // varchar(10), nullable, indexed
            $table->string('itemid', 255)->index(); // varchar(255), indexed
            $table->string('userid', 255)->index(); // varchar(255), indexed
            $table->unsignedBigInteger('host_id')->index(); // bigint(20), indexed
            $table->date('check_in')->index(); // date, indexed
            $table->date('check_out')->nullable()->index(); // date, nullable, indexed
            $table->string('start_time', 10)->nullable(); // varchar(10), nullable
            $table->string('end_time', 10)->nullable(); // varchar(10), nullable
            $table->enum('status', ['Pending', 'Cancelled', 'Confirmed', 'Declined'])->default('Pending'); // enum, default 'Pending'
            $table->integer('total_night'); // int(11), not nullable
            $table->decimal('per_night', 15, 2); // decimal(15,2), not nullable
            $table->string('book_for', 255)->nullable(); // varchar(255), nullable
            $table->decimal('base_price', 15, 2); // decimal(15,2), not nullable
            $table->decimal('cleaning_charge', 15, 2)->default(0.00); // decimal(15,2), default 0.00
            $table->decimal('guest_charge', 15, 2)->default(0.00); // decimal(15,2), default 0.00
            $table->decimal('service_charge', 15, 2)->default(0.00); // decimal(15,2), default 0.00
            $table->decimal('security_money', 15, 2)->default(0.00); // decimal(15,2), default 0.00
            $table->decimal('iva_tax', 15, 2)->default(0.00); // decimal(15,2), default 0.00
            $table->string('coupon_code', 100)->nullable(); // varchar(100), nullable
            $table->double('coupon_discount', 15, 2)->default(0.00); // double(15,2), default 0.00
            $table->double('discount_price', 15, 2)->default(0.00); // double(15,2), default 0.00
            $table->integer('total_guest')->default(0); // int(11), default 0
            $table->double('amount_to_pay', 15, 2)->default(0.00); // double(15,2), default 0.00
            $table->decimal('total', 15, 2)->default(0.00); // decimal(15,2), default 0.00
            $table->decimal('admin_commission', 24, 2)->default(0.00); // decimal(24,2), default 0.00
            $table->decimal('vendor_commission', 24, 2)->default(0.00); // decimal(24,2), default 0.00
            $table->tinyInteger('vendor_commission_given')->default(0); // tinyint(4), default 0
            $table->string('currency_code', 255)->nullable(); // varchar(255), nullable
            $table->string('cancellation_reasion', 255)->nullable(); // varchar(255), nullable
            $table->decimal('cancelled_charge', 15, 2)->default(0.00); // decimal(15,2), default 0.00
            $table->string('transaction', 255)->nullable(); // varchar(255), nullable
            $table->string('payment_method', 255)->nullable(); // varchar(255), nullable
            $table->enum('payment_status', ['notpaid', 'paid', 'offline', ''])->nullable(); // enum, nullable
            $table->string('item_img', 255)->nullable(); // varchar(255), nullable
            $table->string('item_title', 255)->nullable(); // varchar(255), nullable
            $table->text('item_data')->nullable(); // text, nullable
            $table->decimal('wall_amt', 15, 2)->default(0.00); // decimal(15,2), default 0.00
            $table->longText('note')->nullable(); // longtext, nullable
            $table->integer('rating')->default(0); // int(11), default 0
            $table->tinyInteger('module')->default(1); // tinyint(4), default 1
            $table->string('cancelled_by', 255)->nullable(); // varchar(255), nullable
            $table->double('deductedAmount', 15, 2)->default(0.00); // double(15,2), default 0.00
            $table->double('refundableAmount', 15, 2)->default(0.00); // double(15,2), default 0.00
            $table->timestamps(); // created_at and updated_at timestamps
            $table->softDeletes(); // deleted_at timestamp
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bookings');
    }
}
