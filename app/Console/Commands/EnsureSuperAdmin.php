<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class EnsureSuperAdmin extends Command
{
    protected $signature = 'app:ensure-super-admin';

    protected $description = 'Create or update the super admin user and attach Admin role';

    public function handle(): int
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('roles') || ! Schema::hasTable('role_user')) {
            $this->warn('Skipping super admin bootstrap: required tables do not exist yet.');

            return self::SUCCESS;
        }

        $email = env('SUPER_ADMIN_EMAIL', 'admin@admin.com');
        $password = env('SUPER_ADMIN_PASSWORD', 'ChangeMe@123');
        $name = env('SUPER_ADMIN_NAME', 'Super Admin');

        if (empty($email) || empty($password)) {
            $this->warn('Skipping super admin bootstrap: SUPER_ADMIN_EMAIL or SUPER_ADMIN_PASSWORD is empty.');

            return self::SUCCESS;
        }

        DB::beginTransaction();

        try {
            $adminRole = Role::withTrashed()->firstOrCreate(
                ['id' => 1],
                ['title' => 'Admin']
            );

            if ($adminRole->trashed()) {
                $adminRole->restore();
            }

            $user = User::firstOrNew(['email' => $email]);
            $user->name = $name;
            $user->password = Hash::make($password);
            $user->email_verified_at = now();
            $user->save();

            $user->roles()->syncWithoutDetaching([$adminRole->id]);

            DB::commit();

            $this->info("Super admin ensured for {$email}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Failed to ensure super admin: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
