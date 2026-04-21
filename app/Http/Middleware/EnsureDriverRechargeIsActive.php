<?php

namespace App\Http\Middleware;

use App\Models\AppUser;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnsureDriverRechargeIsActive
{
    /**
     * Only ride-gating routes should be blocked when driver recharge is expired/missing.
     * Driver should still be able to log in, access wallet/recharge screens, and use non-ride app features.
     */
    private array $protectedPaths = [
        'api/v1/ride-requests',
        'api/v1/updateBookingStatusByDriver',
        'api/v1/updatePaymentStatusByDriver',
    ];

    public function handle(Request $request, Closure $next)
    {
        if (! $this->isProtectedPath($request)) {
            return $next($request);
        }

        $token = trim((string) $request->input('token', ''));
        if ($token === '') {
            return $next($request);
        }

        $driver = AppUser::where('token', $token)
            ->where('user_type', 'driver')
            ->first();

        if (! $driver) {
            return $next($request);
        }

        if ($this->canDriverRide($driver)) {
            return $next($request);
        }

        return new JsonResponse([
            'status' => 403,
            'message' => 'Driver recharge required',
            'data' => [
                'error_key' => 'recharge_required',
                'recharge_active' => false,
                'recharge_valid_until' => $driver->recharge_valid_until
                    ? Carbon::parse($driver->recharge_valid_until)->toDateTimeString()
                    : null,
            ],
            'error' => 'Driver recharge required',
        ], 403);
    }

    private function isProtectedPath(Request $request): bool
    {
        $path = trim($request->path(), '/');

        if (in_array($path, $this->protectedPaths, true)) {
            return true;
        }

        return preg_match('#^api/v1/ride-requests/\d+/status$#', $path) === 1;
    }

    private function canDriverRide(AppUser $driver): bool
    {
        if (! ((bool) $driver->recharge_active)) {
            return false;
        }

        if (! $driver->recharge_valid_until) {
            $driver->recharge_active = false;
            $driver->save();

            return false;
        }

        $canRide = Carbon::parse($driver->recharge_valid_until)->gte(Carbon::now());

        if (! $canRide && (bool) $driver->recharge_active) {
            $driver->recharge_active = false;
            $driver->save();
        }

        return $canRide;
    }
}
