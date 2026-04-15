<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class ReconcileMigrations extends Command
{
    protected $signature = 'app:reconcile-migrations';

    protected $description = 'Mark create_*_table migrations as executed when tables already exist';

    public function handle(): int
    {
        if (! Schema::hasTable('migrations')) {
            $this->warn('migrations table not found; skipping reconciliation.');

            return self::SUCCESS;
        }

        $maxBatch = (int) DB::table('migrations')->max('batch');
        $batch = max(1, $maxBatch + 1);

        $existingMigrations = DB::table('migrations')
            ->pluck('migration')
            ->toArray();
        $existingMigrationSet = array_fill_keys($existingMigrations, true);

        $migrationFiles = File::files(database_path('migrations'));
        $inserted = 0;

        foreach ($migrationFiles as $file) {
            $migration = pathinfo($file->getFilename(), PATHINFO_FILENAME);

            if (isset($existingMigrationSet[$migration])) {
                continue;
            }

            $table = $this->tableFromCreateMigration($migration);
            if (! $table) {
                continue;
            }

            if (! Schema::hasTable($table)) {
                continue;
            }

            DB::table('migrations')->insert([
                'migration' => $migration,
                'batch' => $batch,
            ]);

            $inserted++;
        }

        $this->info("Reconciled migrations: {$inserted}");

        return self::SUCCESS;
    }

    private function tableFromCreateMigration(string $migration): ?string
    {
        if (preg_match('/^\d{4}_\d{2}_\d{2}_\d+_create_(.+)_table$/', $migration, $matches) !== 1) {
            return null;
        }

        return $matches[1] ?? null;
    }
}

