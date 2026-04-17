<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EmailTemplateDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        $dumpPath = base_path('cravecabsdbone.sql');
        if (! file_exists($dumpPath)) {
            throw new RuntimeException("SQL dump not found at {$dumpPath}");
        }

        $dump = file_get_contents($dumpPath);
        if ($dump === false) {
            throw new RuntimeException("Unable to read SQL dump at {$dumpPath}");
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            DB::table('email_notification_mappings')->truncate();
            DB::table('email_type')->truncate();
            DB::table('email_sms_notification')->truncate();

            DB::unprepared($this->extractInsertStatement($dump, 'email_type'));
            DB::unprepared($this->extractInsertStatement($dump, 'email_sms_notification'));
            DB::unprepared($this->extractInsertStatement($dump, 'email_notification_mappings'));
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function extractInsertStatement(string $dump, string $table): string
    {
        $pattern = '/INSERT INTO `'.preg_quote($table, '/').'`[\s\S]*?;/m';
        if (! preg_match($pattern, $dump, $matches)) {
            throw new RuntimeException("INSERT statement not found for table {$table} in SQL dump.");
        }

        return $matches[0];
    }
}

