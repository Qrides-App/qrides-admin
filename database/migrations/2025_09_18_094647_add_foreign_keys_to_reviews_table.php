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
        if (! Schema::hasTable('reviews') || ! Schema::hasTable('bookings') || ! Schema::hasColumn('reviews', 'bookingid')) {
            return;
        }

        $this->dropForeignsForColumn('reviews', 'bookingid');
        $this->normalizeBookingIdColumnType();

        if (! $this->hasConstraint('reviews', 'fk_reviews_bookingid')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->foreign(['bookingid'], 'fk_reviews_bookingid')
                    ->references(['id'])
                    ->on('bookings')
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
        if (! Schema::hasTable('reviews')) {
            return;
        }

        if ($this->hasConstraint('reviews', 'fk_reviews_bookingid')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->dropForeign('fk_reviews_bookingid');
            });
        }
    }

    private function normalizeBookingIdColumnType(): void
    {
        $column = DB::selectOne(
            'SELECT IS_NULLABLE AS is_nullable, COLUMN_TYPE AS column_type
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?',
            ['reviews', 'bookingid']
        );

        if (! $column) {
            return;
        }

        $nullableSql = ($column->is_nullable ?? 'NO') === 'YES' ? 'NULL' : 'NOT NULL';
        if (strtolower((string) ($column->column_type ?? '')) !== 'bigint(20) unsigned') {
            DB::statement("ALTER TABLE `reviews` MODIFY `bookingid` BIGINT UNSIGNED {$nullableSql}");
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
