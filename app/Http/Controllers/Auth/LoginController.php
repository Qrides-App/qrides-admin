<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\GeneralSetting;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    protected function validateLogin(Request $request)
    {
        $general_captcha = $this->safeSetting('general_captcha');
        $private_key = $this->safeSetting('private_key');
        if ($general_captcha === 'yes') {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
                'g-recaptcha-response' => 'required',
            ]);

            if (empty($private_key)) {
                return redirect()->back()
                    ->withErrors(['g-recaptcha-response' => 'reCAPTCHA is not configured correctly.'])
                    ->withInput();
            }

            $recaptchaResponse = $request->input('g-recaptcha-response');
            $response = Http::post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $private_key,
                'response' => $recaptchaResponse,
                'remoteip' => $request->ip(),
            ]);

            if (! ($response->json()['success'] ?? false)) {
                return redirect()->back()
                    ->withErrors(['g-recaptcha-response' => 'reCAPTCHA verification failed.'])
                    ->withInput();
            }
        }

        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Continue with the login process
    }

    public function login(Request $request): RedirectResponse
    {
        $validationResult = $this->validateLogin($request);
        if ($validationResult instanceof RedirectResponse) {
            return $validationResult;
        }

        try {
            $credentials = $request->only('email', 'password');

            if (Auth::attempt($credentials, $request->boolean('remember'))) {
                $request->session()->regenerate();

                return redirect()->intended($this->redirectTo);
            }

            return back()
                ->withErrors(['email' => trans('auth.failed')])
                ->onlyInput('email');
        } catch (\Throwable $e) {
            Log::error('Login failed with server exception', [
                'error' => $e->getMessage(),
                'email' => $request->input('email'),
            ]);

            return back()
                ->withErrors(['email' => 'Login is temporarily unavailable. Please try again.'])
                ->onlyInput('email');
        }
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function showLoginForm()
    {
        $settings = collect();
        if (Schema::hasTable('general_settings')) {
            $settings = GeneralSetting::whereIn('meta_key', [
                'general_name',
                'general_description',
                'general_logo',
                'general_favicon',
                'general_loginBackgroud',
                'general_captcha',
                'site_key',
                'private_key',
            ])->pluck('meta_value', 'meta_key');
        }

        return view('auth.login', [
            'logoUrl' => !empty($settings['general_logo']) ? url('/media/public/' . ltrim($settings['general_logo'], '/')) : null,
            'siteName' => $settings['general_name'] ?? '',
            'tagLine' => $settings['general_description'] ?? '',
            'faviconUrl' => !empty($settings['general_favicon']) ? url('/media/public/' . ltrim($settings['general_favicon'], '/')) : asset('default/favicon.png'),
            'loginBackgroud' => !empty($settings['general_loginBackgroud']) ? url('/media/public/' . ltrim($settings['general_loginBackgroud'], '/')) : null,
            'general_captcha' => $settings['general_captcha'] ?? '',
            'site_key' => $settings['site_key'] ?? '',
            'private_key' => $settings['private_key'] ?? '',
        ]);
    }

    private function safeSetting(string $key): ?string
    {
        if (! Schema::hasTable('general_settings')) {
            return null;
        }

        return GeneralSetting::where('meta_key', $key)->value('meta_value');
    }
}
