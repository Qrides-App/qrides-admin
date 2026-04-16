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
        if (! Schema::hasTable('rental_item_meta') || ! Schema::hasTable('rental_items')) {
            return;
        }

        $foreignColumn = Schema::hasColumn('rental_item_meta', 'item_id')
            ? 'item_id'
            : (Schema::hasColumn('rental_item_meta', 'rental_item_id') ? 'rental_item_id' : null);

        if (! $foreignColumn) {
            return;
        }

        if (! $this->hasConstraint('rental_item_meta', 'fk_rental_item_meta_item_id')) {
            Schema::table('rental_item_meta', function (Blueprint $table) use ($foreignColumn) {
                $table->foreign([$foreignColumn], 'fk_rental_item_meta_item_id')
                    ->references(['id'])
                    ->on('rental_items')
                    ->onUpdate('no action')
                    ->onDelete('cascade');
            });
        }

        if (! $this->hasConstraint('rental_item_meta', 'rental_item_meta_ibfk_1')) {
            Schema::table('rental_item_meta', function (Blueprint $table) use ($foreignColumn) {
                $table->foreign([$foreignColumn], 'rental_item_meta_ibfk_1')
                    ->references(['id'])
                    ->on('rental_items')
                    ->onUpdate('cascade')
                    ->onDelete('no action');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('rental_item_meta')) {
            return;
        }

        if ($this->hasConstraint('rental_item_meta', 'fk_rental_item_meta_item_id')) {
            Schema::table('rental_item_meta', function (Blueprint $table) {
                $table->dropForeign('fk_rental_item_meta_item_id');
            });
        }

        if ($this->hasConstraint('rental_item_meta', 'rental_item_meta_ibfk_1')) {
            Schema::table('rental_item_meta', function (Blueprint $table) {
                $table->dropForeign('rental_item_meta_ibfk_1');
            });
        }
    }

    private function hasConstraint(string $table, string $constraintName): bool
    {
        $rows = DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?',
            [$table, $constraintName]
        );

        return ! empty($rows);
    }
};
