<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payout_method')) {
            Schema::create('payout_method', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->unsignedTinyInteger('status')->default(1);
                $table->unsignedInteger('module')->nullable();
                $table->timestamps();
            });
        }

        foreach (['upi', 'bank', 'paypal'] as $name) {
            DB::table('payout_method')->updateOrInsert(
                ['name' => $name],
                [
                    'status' => 1,
                    'module' => 2,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_method');
    }
};
