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
     * Routes that remain accessible even when driver recharge is expired/missing.
     */
    private array $allowedPaths = [
        'api/v1/userLogout',
        'api/v1/getgeneralSettings',
        'api/v1/fcmUpdate',
        'api/v1/getRechargePlans',
        'api/v1/getDriverRechargeStatus',
        'api/v1/rechargeWallet',
        'api/v1/startRechargePayment',
        'api/v1/confirmRechargePayment',
        'api/v1/getSupportTickets',
        'api/v1/createSupportTicket',
        'api/v1/replySupportTicket',
    ];

    public function handle(Request $request, Closure $next)
    {
        if ($this->isAllowedPath($request)) {
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

    private function isAllowedPath(Request $request): bool
    {
        $path = trim($request->path(), '/');

        return in_array($path, $this->allowedPaths, true);
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
