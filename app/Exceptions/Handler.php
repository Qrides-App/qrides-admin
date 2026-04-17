<?php

namespace App\Exceptions;

use App\Http\Controllers\Traits\ResponseTrait;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Log;
use Throwable;

class Handler extends ExceptionHandler
{
    use ResponseTrait;

    protected $levels = [];

    protected $dontReport = [];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (QueryException $e, $request) {
            $isAdminRoute = $request->is('admin') || $request->is('admin/*');

            if (! $isAdminRoute) {
                return null;
            }

            Log::error('Admin DB query exception', [
                'path' => $request->path(),
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Some modules are not fully initialized yet. Please try again shortly.',
                ], 500);
            }

            if ($request->routeIs('admin.home')) {
                return null;
            }

            return redirect()
                ->route('admin.home')
                ->with('message', 'Some modules are still initializing. Please try again in a minute.');
        });

        $this->renderable(function (ThrottleRequestsException $e, $request) {
            $retryAfterSeconds = max((int) ($e->getHeaders()['Retry-After'] ?? 60), 1);
            $retryAfterMinutes = (int) ceil($retryAfterSeconds / 60);
            $message = "Too many attempts. Please try again in {$retryAfterSeconds} seconds ({$retryAfterMinutes} minute(s)).";

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 429,
                    'message' => $message,
                    'retry_after_seconds' => $retryAfterSeconds,
                    'retry_after_minutes' => $retryAfterMinutes,
                ], 429);
            }

            return back()->withErrors(['email' => $message])->withInput($request->except('password'));
        });
    }

    public function unauthenticated($request, AuthenticationException $exception)
    {

        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->addSuccessResponse(498, trans('front.Unauthenticated_or_token_expired.'), []);
        }

        return redirect()->guest(route('login'));
    }
}
