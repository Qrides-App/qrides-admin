<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        $this->ensureModuleTable();
        $this->ensureGeneralSettingsTable();
        $this->ensureLanguagesTable();
        $this->ensureRentalItemTypesTable();
        $this->ensureItemCityFareTable();
        $this->ensureDefaultModuleRow();
        $this->ensureDefaultLanguageRow();

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

    private function ensureModuleTable(): void
    {
        if (Schema::hasTable('module')) {
            return;
        }

        Schema::create('module', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->tinyInteger('default_module')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function ensureGeneralSettingsTable(): void
    {
        if (Schema::hasTable('general_settings')) {
            return;
        }

        Schema::create('general_settings', function (Blueprint $table) {
            $table->id();
            $table->string('meta_key')->unique();
            $table->text('meta_value');
            $table->tinyInteger('module')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function ensureDefaultModuleRow(): void
    {
        if (! Schema::hasTable('module')) {
            return;
        }

        $defaultModuleExists = DB::table('module')
            ->where('default_module', 1)
            ->exists();

        if ($defaultModuleExists) {
            return;
        }

        $now = now();

        DB::table('module')->insert([
            'name' => 'Ride',
            'description' => 'Default module',
            'status' => 1,
            'default_module' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function ensureLanguagesTable(): void
    {
        if (Schema::hasTable('languages')) {
            return;
        }

        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('short_name');
            $table->boolean('language_status')->default(1);
            $table->timestamps();
        });
    }

    private function ensureDefaultLanguageRow(): void
    {
        if (! Schema::hasTable('languages')) {
            return;
        }

        $defaultLanguageExists = DB::table('languages')->exists();
        if ($defaultLanguageExists) {
            return;
        }

        DB::table('languages')->insert([
            'name' => 'English',
            'short_name' => 'en',
            'language_status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function ensureRentalItemTypesTable(): void
    {
        if (Schema::hasTable('rental_item_types')) {
            return;
        }

        Schema::create('rental_item_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('status')->default('1');
            $table->boolean('module')->nullable()->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function ensureItemCityFareTable(): void
    {
        if (Schema::hasTable('item_city_fare')) {
            return;
        }

        Schema::create('item_city_fare', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('item_type_id')->index('item_type_id');
            $table->decimal('min_fare', 10)->nullable();
            $table->decimal('max_fare', 10)->nullable();
            $table->decimal('recommended_fare', 10)->default(0);
            $table->decimal('admin_commission', 5)->default(0);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
        });
    }
}
