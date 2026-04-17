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
        $this->ensureAllPackagesTable();
        $this->ensureAppUsersTable();
        $this->ensureAppUsersPackageColumn();
        $this->ensureAppUserMetaTable();
        $this->normalizeAppUserMetaForeignKeyColumns();
        $this->ensurePayoutsTable();
        $this->ensureDriverRechargePlansTable();
        $this->ensureSosNumbersTable();
        $this->normalizePayoutForeignKeyColumns();
        $this->ensureEmailTypeTable();
        $this->ensureEmailSmsNotificationTable();
        $this->ensureEmailNotificationMappingsTable();
        $this->normalizeEmailNotificationMappingForeignKeyColumns();
        $this->ensureAppUsersBankAccountsTable();
        $this->ensureAppUserOtpsTable();
        $this->ensureLegacyVehicleMakesTable();
        $this->ensureLegacyItemTypesTable();
        $this->ensureLegacyItemsTable();
        $this->ensureRentalItemsTable();
        $this->ensureBookingsTable();
        $this->ensureBookingMetaTable();
        $this->ensureLanguagesTable();
        $this->ensureSupportTicketsTable();
        $this->ensureSupportTicketRepliesTable();
        $this->normalizeSupportTicketReplyForeignKeyColumns();
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
            $table->unsignedBigInteger('package_id')->nullable()->default(1);
            $table->decimal('wallet', 15)->nullable();
            $table->decimal('ave_host_rate', 15)->default(0);
            $table->decimal('avr_guest_rate', 15)->default(0);
            $table->text('fcm')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function ensureAllPackagesTable(): void
    {
        if (Schema::hasTable('all_packages')) {
            return;
        }

        Schema::create('all_packages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('price')->nullable();
            $table->integer('days')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function ensureAppUsersPackageColumn(): void
    {
        if (! Schema::hasTable('app_users')) {
            return;
        }

        if (! Schema::hasColumn('app_users', 'package_id')) {
            Schema::table('app_users', function (Blueprint $table) {
                $table->unsignedBigInteger('package_id')->nullable()->default(1)->after('status');
            });
        }

        try {
            $appUsersIdType = $this->getColumnType('all_packages', 'id');
            if ($appUsersIdType) {
                DB::statement("ALTER TABLE `app_users` MODIFY `package_id` {$appUsersIdType} NULL");
            }
        } catch (\Throwable $e) {
            // Ignore type normalization failures at bootstrap.
        }
    }

    private function ensureAppUserMetaTable(): void
    {
        if (Schema::hasTable('app_user_meta')) {
            return;
        }

        Schema::create('app_user_meta', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable()->index('user_id');
            $table->string('meta_key')->nullable()->index('meta_key');
            $table->longText('meta_value')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
            $table->unique(['user_id', 'meta_key'], 'user_id_2');
        });
    }

    private function normalizeAppUserMetaForeignKeyColumns(): void
    {
        if (! Schema::hasTable('app_user_meta') || ! Schema::hasTable('app_users')) {
            return;
        }

        try {
            if (Schema::hasColumn('app_user_meta', 'user_id') && Schema::hasColumn('app_users', 'id')) {
                $appUserIdColumnType = $this->getColumnType('app_users', 'id');
                if ($appUserIdColumnType) {
                    DB::statement("ALTER TABLE `app_user_meta` MODIFY `user_id` {$appUserIdColumnType} NULL");
                }
            }
        } catch (\Throwable $e) {
            // Ignore normalization failures; startup should continue.
        }
    }

    private function ensureEmailTypeTable(): void
    {
        if (Schema::hasTable('email_type')) {
            return;
        }

        Schema::create('email_type', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }

    private function ensureEmailSmsNotificationTable(): void
    {
        if (Schema::hasTable('email_sms_notification')) {
            return;
        }

        Schema::create('email_sms_notification', function (Blueprint $table) {
            $table->increments('id');
            $table->string('temp_name', 250)->nullable();
            $table->tinyInteger('module')->default(1);
            $table->string('role', 250)->nullable();
            $table->string('subject', 191)->nullable();
            $table->text('body')->nullable();
            $table->string('lang', 10)->nullable();
            $table->timestamps();
        });
    }

    private function ensureEmailNotificationMappingsTable(): void
    {
        if (Schema::hasTable('email_notification_mappings')) {
            return;
        }

        Schema::create('email_notification_mappings', function (Blueprint $table) {
            $table->unsignedInteger('email_type_id');
            $table->unsignedInteger('email_sms_notification_id')->index('email_sms_notification_id');
            $table->unsignedInteger('module')->default(1);
            $table->primary(['email_type_id', 'email_sms_notification_id', 'module']);
        });
    }

    private function ensurePayoutsTable(): void
    {
        if (Schema::hasTable('payouts')) {
            return;
        }

        Schema::create('payouts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('vendorid')->nullable()->index('fk_payouts_vendorid');
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('currency')->nullable();
            $table->string('request_by')->nullable()->default('vendor');
            $table->string('payment_method')->nullable();
            $table->enum('payout_status', ['Pending', 'Success', 'Rejected'])->nullable();
            $table->longText('note')->nullable();
            $table->tinyInteger('module')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function ensureDriverRechargePlansTable(): void
    {
        if (Schema::hasTable('driver_recharge_plans')) {
            return;
        }

        Schema::create('driver_recharge_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('duration_days');
            $table->decimal('amount', 15, 2);
            $table->string('currency_code', 10)->default('INR');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function ensureSosNumbersTable(): void
    {
        if (Schema::hasTable('sos_numbers')) {
            return;
        }

        Schema::create('sos_numbers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('sos_number');
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);
            $table->tinyInteger('module')->default(2);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function normalizePayoutForeignKeyColumns(): void
    {
        if (! Schema::hasTable('payouts') || ! Schema::hasTable('app_users')) {
            return;
        }

        try {
            if (Schema::hasColumn('payouts', 'vendorid') && Schema::hasColumn('app_users', 'id')) {
                $userIdType = $this->getColumnType('app_users', 'id');
                if ($userIdType) {
                    DB::statement("ALTER TABLE `payouts` MODIFY `vendorid` {$userIdType} NULL");
                }
            }
        } catch (\Throwable $e) {
            // Ignore normalization failures; startup should continue.
        }
    }

    private function normalizeEmailNotificationMappingForeignKeyColumns(): void
    {
        if (! Schema::hasTable('email_notification_mappings') || ! Schema::hasTable('email_sms_notification')) {
            return;
        }

        try {
            $smsIdType = $this->getColumnType('email_sms_notification', 'id');
            if ($smsIdType && Schema::hasColumn('email_notification_mappings', 'email_sms_notification_id')) {
                DB::statement("ALTER TABLE `email_notification_mappings` MODIFY `email_sms_notification_id` {$smsIdType} NOT NULL");
            }

            if (Schema::hasTable('email_type') && Schema::hasColumn('email_notification_mappings', 'email_type_id')) {
                $emailTypeIdType = $this->getColumnType('email_type', 'id');
                if ($emailTypeIdType) {
                    DB::statement("ALTER TABLE `email_notification_mappings` MODIFY `email_type_id` {$emailTypeIdType} NOT NULL");
                }
            }
        } catch (\Throwable $e) {
            // Ignore normalization failures; startup should continue.
        }
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

    private function ensureAppUserOtpsTable(): void
    {
        if (Schema::hasTable('app_user_otps')) {
            return;
        }

        Schema::create('app_user_otps', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 15);
            $table->string('country_code', 5);
            $table->string('otp_code', 10);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable();
            // MySQL 8+ safe (avoid invalid zero-datetime defaults)
            $table->timestamp('expires_at')->nullable();
            $table->index('phone');
            $table->index('otp_code');
        });
    }

    private function ensureAppUsersBankAccountsTable(): void
    {
        if (Schema::hasTable('app_users_bank_accounts')) {
            return;
        }

        Schema::create('app_users_bank_accounts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('account_name')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('branch_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('iban')->nullable();
            $table->string('swift_code')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
        });
    }

    private function ensureSupportTicketsTable(): void
    {
        if (Schema::hasTable('support_tickets')) {
            return;
        }

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('thread_id', 20)->nullable();
            $table->integer('thread_status')->default(1);
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->tinyInteger('module')->default(2);
            $table->timestamps();
        });
    }

    private function ensureSupportTicketRepliesTable(): void
    {
        if (Schema::hasTable('support_ticket_replies')) {
            return;
        }

        Schema::create('support_ticket_replies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('thread_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->boolean('is_admin_reply')->default(false);
            $table->text('message')->nullable();
            $table->integer('reply_status')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function normalizeSupportTicketReplyForeignKeyColumns(): void
    {
        if (! Schema::hasTable('support_tickets') || ! Schema::hasTable('support_ticket_replies')) {
            return;
        }

        try {
            if (Schema::hasColumn('support_ticket_replies', 'thread_id') && Schema::hasColumn('support_tickets', 'id')) {
                $supportIdColumnType = $this->getColumnType('support_tickets', 'id');
                if ($supportIdColumnType) {
                    DB::statement("ALTER TABLE `support_ticket_replies` MODIFY `thread_id` {$supportIdColumnType} NOT NULL");
                }
            }

            if (Schema::hasTable('users') && Schema::hasColumn('support_ticket_replies', 'user_id') && Schema::hasColumn('users', 'id')) {
                $userIdColumnType = $this->getColumnType('users', 'id');
                if ($userIdColumnType) {
                    DB::statement("ALTER TABLE `support_ticket_replies` MODIFY `user_id` {$userIdColumnType} NULL");
                }
            }
        } catch (\Throwable $e) {
            // Ignore normalization failures; startup should continue.
        }
    }

    private function getColumnType(string $table, string $column): ?string
    {
        $databaseName = DB::getDatabaseName();

        $columnType = DB::table('information_schema.columns')
            ->where('table_schema', $databaseName)
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->value('column_type');

        return is_string($columnType) && $columnType !== '' ? $columnType : null;
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

    private function ensureLegacyVehicleMakesTable(): void
    {
        if (Schema::hasTable('vehicle_makes')) {
            return;
        }

        Schema::create('vehicle_makes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 50)->nullable();
            $table->integer('module')->default(1);
            $table->string('description')->nullable();
            $table->boolean('status')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function ensureBookingMetaTable(): void
    {
        if (Schema::hasTable('booking_meta')) {
            return;
        }

        Schema::create('booking_meta', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->string('meta_key')->nullable();
            $table->longText('meta_value')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
        });
    }
}
