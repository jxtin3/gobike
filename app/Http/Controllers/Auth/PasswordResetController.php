<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\PasswordResetCodes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * "Forgot password?" on the admin sign-in page: an emailed 6-digit code, then a new password.
 * The email is kept in the session between the two pages.
 */
class PasswordResetController extends Controller
{
    public function request(): View
    {
        return view('auth.forgot-password');
    }

    public function email(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);

        PasswordResetCodes::send($data['email']);
        $request->session()->put('password_reset_email', PasswordResetCodes::normalize($data['email']));

        return redirect()->route('password.reset')->with('status', PasswordResetCodes::SENT_MESSAGE);
    }

    public function edit(Request $request): View|RedirectResponse
    {
        $email = $request->session()->get('password_reset_email');
        if (! $email) {
            return redirect()->route('password.request');
        }

        return view('auth.reset-password', ['email' => $email, 'minutes' => PasswordResetCodes::MINUTES]);
    }

    public function update(Request $request): RedirectResponse
    {
        $email = $request->session()->get('password_reset_email');
        if (! $email) {
            return redirect()->route('password.request');
        }

        // The same rules as `php artisan admin:create`: only admins sign in here.
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', 'max:100', Password::min(12)->letters()->numbers()],
        ]);

        if (! PasswordResetCodes::check($email, $data['code'])) {
            return back()->withErrors(['code' => PasswordResetCodes::WRONG_CODE]);
        }

        PasswordResetCodes::reset($email, $data['password']);
        $request->session()->forget('password_reset_email');

        return redirect()->route('login')->with('status', 'Password changed. Sign in with your new password.');
    }
}
