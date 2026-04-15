<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class AuthGates
{
    public function handle($request, Closure $next)
    {
        try {
            Gate::before(function ($user, $ability) {
                // Bootstrap-safe super-admin bypass so full menu/actions stay available
                // while permission tables are still being seeded.
                if ((int) ($user->id ?? 0) === 1) {
                    return true;
                }

                try {
                    if (method_exists($user, 'roles') && $user->roles()->where('id', 1)->exists()) {
                        return true;
                    }
                } catch (\Throwable $e) {
                    // Ignore relation failures here; normal gate checks continue below.
                }

                return null;
            });

            $user = auth()->user();

            if (! $user) {
                return $next($request);
            }

            $roles = cache()->remember('roles_with_permissions', now()->addHours(6), function () {
                return Role::with('permissions')->get();
            });
            $permissionsArray = [];

            foreach ($roles as $role) {
                foreach ($role->permissions as $permissions) {
                    $permissionsArray[$permissions->title][] = $role->id;
                }
            }

            foreach ($permissionsArray as $title => $roles) {
                Gate::define($title, function ($user) use ($roles) {
                    return count(array_intersect($user->roles->pluck('id')->toArray(), $roles)) > 0;
                });
            }
        } catch (\Throwable $e) {
            Log::error('AuthGates middleware failed', [
                'error' => $e->getMessage(),
                'path' => $request->path(),
            ]);
        }

        return $next($request);
    }
}
