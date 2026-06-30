<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Email Verification Controller
    |--------------------------------------------------------------------------
    |
    | This controller verifies user accounts via a signed link sent by email.
    | The user is never authenticated when clicking the link (registration no
    | longer logs them in), so verification relies solely on the signature
    | and the id/hash pair instead of the `auth` middleware.
    |
    */

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('signed')->only('verify');
        $this->middleware('throttle:6,1')->only('verify', 'resend');
    }

    /**
     * Redirect requests to the unused "verification notice" route.
     *
     * Accounts are never logged in before verification, so there's no
     * post-login notice screen — only the resend form is reachable from the UI.
     */
    public function show(): RedirectResponse
    {
        return redirect()->route('verification.resend.form');
    }

    /**
     * Mark the given user's email as verified.
     */
    public function verify(Request $request, string $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        abort_unless(
            hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification())),
            403
        );

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('login')
                ->with('status', 'Tu cuenta ya estaba verificada. Ya puedes iniciar sesión.');
        }

        $user->markEmailAsVerified();

        event(new Verified($user));

        return redirect()->route('login')
            ->with('status', 'Tu cuenta ha sido verificada. Ya puedes iniciar sesión.');
    }

    /**
     * Resend the email verification notification.
     */
    public function resend(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $request->input('email'))->first();

        if ($user && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return back()->with('status', 'Si la cuenta existe y no ha sido verificada, te hemos enviado un nuevo correo de verificación.');
    }
}
