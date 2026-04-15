<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EnsureAuthSchema extends Command
{
    protected $signature = 'app:ensure-auth-schema';

    protected $description = 'Create essential auth/authorization tables if migrations were partially applied';

    public function handle(): int
    {
        $this->ensureUsersTable();
        $this->ensureRolesTable();
        $this->ensurePermissionsTable();
        $this->ensureRoleUserTable();
        $this->ensurePermissionRoleTable();
        $this->ensurePasswordResetsTable();
        $this->ensurePersonalAccessTokensTable();

        $this->info('Auth schema check completed.');

        return self::SUCCESS;
    }

    private function ensureUsersTable(): void
    {
        if (Schema::hasTable('users')) {
            return;
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->dateTime('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function ensureRolesTable(): void
    {
        if (Schema::hasTable('roles')) {
            return;
        }

        Schema::create('roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function ensurePermissionsTable(): void
    {
        if (Schema::hasTable('permissions')) {
            return;
        }

        Schema::create('permissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function ensureRoleUserTable(): void
    {
        if (Schema::hasTable('role_user')) {
            return;
        }

        Schema::create('role_user', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->index('user_id_fk_8655798');
            $table->unsignedBigInteger('role_id')->index('role_id_fk_8655798');
        });
    }

    private function ensurePermissionRoleTable(): void
    {
        if (Schema::hasTable('permission_role')) {
            return;
        }

        Schema::create('permission_role', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id')->index('permission_id_fk_8655789');
            $table->unsignedBigInteger('role_id')->index('role_id_fk_8655789');
        });
    }

    private function ensurePasswordResetsTable(): void
    {
        if (Schema::hasTable('password_resets')) {
            return;
        }

        Schema::create('password_resets', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    private function ensurePersonalAccessTokensTable(): void
    {
        if (Schema::hasTable('personal_access_tokens')) {
            return;
        }

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }
}

