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
        $this->ensureAppUsersTable();
        $this->ensureLegacyItemTypesTable();
        $this->ensureLegacyItemsTable();
        $this->ensureRentalItemsTable();
        $this->ensureBookingsTable();
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

    private function ensureAppUsersTable(): void
    {
        if (Schema::hasTable('app_users')) {
            return;
        }

        Schema::create('app_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('firestore_id')->nullable()->index('firestore_id');
            $table->string('first_name')->nullable();
            $table->string('middle1')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('phone_country')->nullable();
            $table->string('password')->nullable();
            $table->string('user_type')->default('user');
            $table->enum('host_status', ['0', '1', '2'])->nullable()->default('0');
            $table->boolean('status')->nullable()->default(true);
            $table->decimal('wallet', 15)->nullable();
            $table->decimal('ave_host_rate', 15)->default(0);
            $table->decimal('avr_guest_rate', 15)->default(0);
            $table->text('fcm')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function ensureRentalItemsTable(): void
    {
        if (Schema::hasTable('rental_items')) {
            return;
        }

        Schema::create('rental_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('token', 191)->nullable()->index('token');
            $table->string('title')->nullable();
            $table->double('item_rating', 15, 2)->nullable()->default(0);
            $table->decimal('average_speed_kmph', 15)->default(40);
            $table->double('longitude', null, 0)->nullable();
            $table->double('latitude', null, 0)->nullable();
            $table->unsignedBigInteger('userid_id')->nullable()->index('userid_fk_8656820');
            $table->unsignedBigInteger('item_type_id')->nullable()->index('property_type_fk_8657403');
            $table->unsignedBigInteger('place_id')->nullable()->index('place_fk_8657368');
            $table->string('make')->nullable();
            $table->string('model')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('service_type', 25)->nullable();
            $table->integer('module')->nullable()->default(2);
            $table->tinyInteger('is_featured')->nullable()->default(0);
            $table->boolean('is_verified')->nullable()->default(false);
            $table->boolean('status')->nullable()->default(false);
            $table->integer('views_count')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function ensureBookingsTable(): void
    {
        if (Schema::hasTable('bookings')) {
            return;
        }

        Schema::create('bookings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('token', 10)->nullable()->index('token');
            $table->string('itemid')->nullable()->index('itemid');
            $table->string('userid')->nullable()->index('userid');
            $table->bigInteger('host_id')->nullable()->index('host_id');
            $table->date('ride_date')->nullable()->index('check_in');
            $table->enum('status', ['Pending', 'Ongoing', 'Arrived', 'Accepted', 'Cancelled', 'Confirmed', 'Declined', 'Expired', 'Refunded', 'Completed', 'Rejected'])->default('Pending')->index('status');
            $table->decimal('price_per_km', 15)->default(0);
            $table->decimal('base_price', 15)->default(0);
            $table->decimal('service_charge', 15)->nullable()->default(0);
            $table->decimal('iva_tax', 15)->nullable()->default(0);
            $table->double('amount_to_pay', 15, 2)->nullable()->default(0);
            $table->decimal('total', 15)->nullable()->default(0);
            $table->decimal('admin_commission', 24)->default(0);
            $table->decimal('vendor_commission', 24)->default(0);
            $table->tinyInteger('vendor_commission_given')->default(0);
            $table->string('currency_code')->nullable();
            $table->string('cancellation_reasion')->nullable();
            $table->string('transaction')->nullable();
            $table->string('payment_method')->nullable();
            $table->enum('payment_status', ['notpaid', 'pending', 'paid', 'offline', ''])->nullable();
            $table->longText('firebase_json')->nullable();
            $table->decimal('wall_amt', 15)->nullable()->default(0);
            $table->integer('rating')->default(0);
            $table->tinyInteger('module')->default(2);
            $table->string('cancelled_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function ensureLegacyItemTypesTable(): void
    {
        if (Schema::hasTable('item_types')) {
            return;
        }

        Schema::create('item_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->string('description')->nullable();
            $table->string('status')->default('1');
            $table->integer('module')->nullable()->default(2);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function ensureLegacyItemsTable(): void
    {
        if (Schema::hasTable('items')) {
            return;
        }

        Schema::create('items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('token', 191)->nullable()->index('token');
            $table->string('title')->nullable();
            $table->unsignedBigInteger('userid_id')->nullable();
            $table->unsignedBigInteger('item_type_id')->nullable();
            $table->unsignedBigInteger('place_id')->nullable();
            $table->integer('module')->nullable()->default(2);
            $table->boolean('status')->nullable()->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
