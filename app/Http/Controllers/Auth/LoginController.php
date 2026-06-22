<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\RecaptchaV3;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * Validate the user login request.
     *
     * @throws ValidationException
     */
    protected function validateLogin(Request $request): void
    {
        $rules = [
            $this->username() => ['required', 'string'],
            'password' => ['required', 'string'],
        ];

        if ($this->recaptchaEnabled()) {
            $rules['recaptcha_token'] = ['required', new RecaptchaV3(
                (string) config('services.recaptcha_v3.action', 'login'),
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
