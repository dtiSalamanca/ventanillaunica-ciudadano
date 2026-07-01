<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\RecaptchaV3;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;

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

    /**
     * Validate the email for the given request.
     */
    protected function validateEmail(Request $request): void
    {
        $rules = ['email' => 'required|email'];

        if ($this->recaptchaEnabled()) {
            $rules['recaptcha_token'] = ['required', new RecaptchaV3(
                'password_email',
                (float) config('services.recaptcha_v3.score_threshold', 0.5),
            )];
        }

        $request->validate($rules);
    }

    /**
     * Determine if reCAPTCHA v3 is enabled and properly configured.
     */
    private function recaptchaEnabled(): bool
    {
        return config('services.recaptcha_v3.enabled', false)
            && filled(config('services.recaptcha_v3.site_key'))
            && filled(config('services.recaptcha_v3.secret_key'));
    }
}
