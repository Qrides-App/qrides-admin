<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class SetLocale
{
    public function handle($request, Closure $next)
    {
        try {
            if (request('change_language')) {
                session()->put('language', request('change_language'));
                $language = request('change_language');
            } elseif (session('language')) {
                $language = session('language');
            } elseif (config('global.primary_language')) {
                $language = config('global.primary_language');
            }

            if (isset($language)) {
                app()->setLocale($language);
            }
        } catch (\Throwable $e) {
            Log::error('SetLocale middleware failed', [
                'error' => $e->getMessage(),
                'path' => $request->path(),
            ]);
        }

        return $next($request);
    }
}
