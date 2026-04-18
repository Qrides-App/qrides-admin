<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\MailSettings;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

    public function __construct()
    {
        MailSettings::apply();
    }

    public function sendResetLinkEmail(Request $request)
    {

        $request->validate(['email' => 'required|email']);

        if (! MailSettings::isConfigured()) {
            return redirect()->route('password.request')
                ->with('error', 'Email configuration is incomplete or disabled. Please contact the administrator.');
        }
        try {

            $response = $this->broker()->sendResetLink(
                $this->credentials($request)
            );

            return $response == Password::RESET_LINK_SENT
                ? $this->sendResetLinkResponse($request, $response)
                : $this->sendResetLinkFailedResponse($request, $response);
        } catch (\Exception $e) {

            return redirect()->route('password.request')->with('error', $e->getMessage());
        }
    }

    protected function credentials(Request $request)
    {
        return $request->only('email');
    }

    public function broker()
    {
        return Password::broker();
    }

    protected function sendResetLinkFailedResponse(Request $request, $response)
    {

        return back()->withErrors(
            ['email' => trans($response)]
        );
    }

    public function sendResetLinkResponse(Request $request, $response)
    {

        return redirect()->route('password.request')->with('status', trans($response));
    }
}
