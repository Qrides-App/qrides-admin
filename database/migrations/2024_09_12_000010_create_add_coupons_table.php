<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAddCouponsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('add_coupons', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('coupon_title', 255); // Title of the coupon with length 255
            $table->string('coupon_subtitle', 255)->nullable(); // Subtitle of the coupon, nullable
            $table->string('coupon_image', 255)->nullable(); // Image URL for the coupon, nullable
            $table->date('coupon_expiry_date')->nullable(); // Expiry date of the coupon, nullable
            $table->string('coupon_code', 255)->unique(); // Unique code for the coupon
            $table->decimal('min_order_amount', 15, 2)->nullable(); // Minimum order amount, nullable
            $table->decimal('coupon_value', 15, 2); // Value of the coupon
            $table->text('coupon_description')->nullable(); // Description of the coupon, nullable
            $table->string('status', 255)->nullable(); // Status of the coupon, nullable
            $table->tinyInteger('module')->default(1); // Module field with a default value of 1
            $table->timestamps(); // Adds created_at and updated_at columns
            $table->softDeletes(); // Adds deleted_at column for soft deletes

            // Index for status
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('add_coupons');
    }
}
