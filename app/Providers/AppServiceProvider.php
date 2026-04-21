<?php

namespace App\Providers;

use App\Models\GeneralSetting;
use App\Models\Module;
use App\Services\FirestoreService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FirestoreService::class, function ($app) {
            return new FirestoreService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
            Config::set('cache.default', 'array');
        }

        $this->ensureStorageFrameworkDirectories();

        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            return; // Stop executing further code if DB not available
        }

        $this->ensureDefaultModuleExists();

        app()->singleton('currentModule', function () {
            return Cache::remember('current_module_default', 6000, function () {
                if (! Schema::hasTable('module')) {
                    return null;
                }

                return Module::where('default_module', '1')->first() ?? Module::query()->first();
            });
        });

        try {
            $settings = Cache::rememberForever('general_settings', function () {
                if (! Schema::hasTable('general_settings')) {
                    return [];
                }

                return GeneralSetting::pluck('meta_value', 'meta_key')->toArray();
            });

            foreach ($settings as $key => $value) {
                Config::set("general.$key", $value);
            }
        } catch (\Exception $e) {
            // silently skip if settings cannot be loaded
        }
    }

    private function ensureDefaultModuleExists(): void
    {
        try {
            if (! Schema::hasTable('module')) {
                return;
            }

            $defaultExists = DB::table('module')
                ->whereNull('deleted_at')
                ->where('default_module', 1)
                ->exists();

            if ($defaultExists) {
                return;
            }

            $firstModule = DB::table('module')
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->first();

            if ($firstModule) {
                DB::table('module')
                    ->where('id', $firstModule->id)
                    ->update([
                        'default_module' => 1,
                        'status' => 1,
                        'updated_at' => now(),
                    ]);

                return;
            }

            DB::table('module')->insert([
                'name' => 'Ride',
                'description' => 'Default module',
                'status' => 1,
                'default_module' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('ensureDefaultModuleExists failed', ['error' => $e->getMessage()]);
        }
    }

    private function ensureStorageFrameworkDirectories(): void
    {
        try {
            $paths = [
                storage_path('framework'),
                storage_path('framework/cache'),
                storage_path('framework/cache/data'),
                storage_path('framework/sessions'),
                storage_path('framework/views'),
                storage_path('logs'),
            ];

            foreach ($paths as $path) {
                if (! File::isDirectory($path)) {
                    File::makeDirectory($path, 0775, true, true);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('ensureStorageFrameworkDirectories failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
