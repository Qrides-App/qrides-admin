<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('wallets') || ! Schema::hasTable('app_users') || ! Schema::hasColumn('wallets', 'user_id')) {
            return;
        }

        $this->dropForeignsForColumn('wallets', 'user_id');
        $this->normalizeUserIdColumnType();

        if (! $this->hasConstraint('wallets', 'fk_wallets_user_id')) {
            Schema::table('wallets', function (Blueprint $table) {
                $table->foreign(['user_id'], 'fk_wallets_user_id')
                    ->references(['id'])
                    ->on('app_users')
                    ->onUpdate('no action')
                    ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('wallets')) {
            return;
        }

        if ($this->hasConstraint('wallets', 'fk_wallets_user_id')) {
            Schema::table('wallets', function (Blueprint $table) {
                $table->dropForeign('fk_wallets_user_id');
            });
        }
    }

    private function normalizeUserIdColumnType(): void
    {
        $column = DB::selectOne(
            'SELECT IS_NULLABLE AS is_nullable, COLUMN_TYPE AS column_type
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?',
            ['wallets', 'user_id']
        );

        if (! $column) {
            return;
        }

        $nullableSql = ($column->is_nullable ?? 'NO') === 'YES' ? 'NULL' : 'NOT NULL';
        if (strtolower((string) ($column->column_type ?? '')) !== 'bigint(20) unsigned') {
            DB::statement("ALTER TABLE `wallets` MODIFY `user_id` BIGINT UNSIGNED {$nullableSql}");
        }
    }

    private function dropForeignsForColumn(string $table, string $column): void
    {
        $constraints = DB::select(
            'SELECT DISTINCT CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$table, $column]
        );

        foreach ($constraints as $constraint) {
            $name = $constraint->CONSTRAINT_NAME ?? null;
            if ($name) {
                DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$name}`");
            }
        }
    }

    private function hasConstraint(string $table, string $constraintName): bool
    {
        $rows = DB::select(
            'SELECT CONSTRAINT_NAME
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ?',
            [$table, $constraintName]
        );

        return ! empty($rows);
    }
};
