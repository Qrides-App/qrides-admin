<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('app_users_bank_accounts', function (Blueprint $table) {
            $table->foreign(['user_id'], 'fk_bank_account_user')->references(['id'])->on('app_users')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_users_bank_accounts', function (Blueprint $table) {
            $table->dropForeign('fk_bank_account_user');
        });
    }
};
