<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_recharge_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->string('public_token', 64)->unique();
            $table->unsignedBigInteger('driver_id')->index();
            $table->unsignedBigInteger('driver_recharge_plan_id')->nullable()->index();
            $table->string('payment_method', 40)->nullable();
            $table->string('payment_status', 40)->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('currency_code', 10)->default('INR');
            $table->unsignedInteger('duration_days')->default(0);
            $table->decimal('taxable_amount', 15, 2)->default(0);
            $table->decimal('gst_rate', 5, 2)->default(0);
            $table->decimal('gst_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamp('invoice_date')->nullable();
            $table->longText('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_recharge_invoices');
    }
};
