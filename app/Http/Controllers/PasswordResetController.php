<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * Forgotten passwords for this site's own sign-in.
 *
 * The `password_reset_tokens` table is shared with the learning portal, so a
 * link issued on either site resets the same account — which is right, because
 * it is one account. The two sites differ only in where they send you
 * afterwards: students and facilitators sign in at the portal, and this site's
 * login opens onto an admin-only area, so the forgot page says so rather than
 * letting somebody reset a password and then bounce off a 403.
 */
class PasswordResetController extends Controller
{
    public function request()
    {
        return view('auth.forgot-password');
    }

    public function email(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        try {
            $status = Password::sendResetLink($request->only('email'));
        } catch (\Throwable $e) {
            // A misconfigured mailer throws here, and a missing transport
            // bridge throws an Error rather than an Exception. Unguarded that
            // is a 500 on a public page; the reason belongs in the log.
            report($e);
            $status = null;
        }

        // The same answer either way, a failure included: a message that only
        // appears for real accounts tells a stranger which addresses exist
        // just as plainly as being asked would.
        return back()->with('status', $status === Password::RESET_LINK_SENT
            ? __($status)
            : 'If that address has an account, a reset link is on its way.');
    }

    public function reset(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    // Choosing their own settles the temporary one we issued,
                    // which is what the portal's password gate reads.
                    'must_change_password' => false,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', 'Your password has been reset — sign in with it now.')
            : back()->withErrors(['email' => __($status)]);
    }
}
