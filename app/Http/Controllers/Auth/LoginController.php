<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $emailKey = 'login:'.Str::lower($credentials['email']).'|'.$request->ip();
        $ipKey = 'login-ip:'.$request->ip();

        if (RateLimiter::tooManyAttempts($emailKey, 5) || RateLimiter::tooManyAttempts($ipKey, 20)) {
            $seconds = max(RateLimiter::availableIn($emailKey), RateLimiter::availableIn($ipKey));
            $message = "Too many login attempts. Please try again in {$seconds} seconds.";

            if ($request->expectsJson()) {
               return response()->json(['message' => $message], 429);
            }

            return back()->withErrors(['email' => $message])->onlyInput('email');
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::clear($emailKey);
            $request->session()->regenerate();
            
            $user = Auth::user();

            if ($user && $user->is_admin && ($user->status ?? 'Active') === 'Active') {
                // If MFA is enabled, redirect to verification instead of dashboard
                if ($user->hasMfaEnabled()) {
                    $request->session()->put('mfa_pending_user', $user->id);
                    $redirect = redirect()->intended(route('mfa.verify'))->getTargetUrl();

                    if ($request->expectsJson()) {
                        return response()->json(['redirect' => $redirect, 'mfa_required' => true]);
                    }

                    return redirect()->intended(route('mfa.verify'));
                }

                $redirect = redirect()->intended(route('admin.dashboard'))->getTargetUrl();
                $request->session()->flash('admin_dashboard_entrance', true);

                if ($request->expectsJson()) {
                    return response()->json(['redirect' => $redirect]);
                }

                return redirect()->intended(route('admin.dashboard'));
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = 'You do not have access to the admin area.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }

            return back()->withErrors([
                'email' => $message,
            ]);
        }
        
        RateLimiter::hit($emailKey, 60);
        RateLimiter::hit($ipKey, 300);
        $message = 'These credentials do not match our records.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return back()->withErrors([
            'email' => $message,
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Show the MFA verification page (after successful password auth).
     */
    public function showMfaVerify(Request $request)
    {
        $userId = $request->session()->get('mfa_pending_user');

        if (! $userId) {
            return redirect()->route('login');
        }

        return view('auth.mfa-verify');
    }

    /**
     * Verify MFA code and complete login.
     */
    public function verifyMfa(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $userId = $request->session()->pull('mfa_pending_user');

        if (! $userId) {
            return redirect()->route('login')->withErrors(['code' => 'Invalid session. Please sign in again.']);
        }

        $user = \App\Models\User::find($userId);

        if (! $user || ! $user->hasMfaEnabled()) {
            return redirect()->route('login')->withErrors(['code' => 'MFA not configured for this account.']);
        }

        $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);
        $valid = $google2fa->verifyKey($user->mfa_secret, $data['code']);

        // Also check backup codes
        if (! $valid && $user->mfa_backup_codes) {
            foreach ($user->mfa_backup_codes as $hashedCode) {
                if (\Illuminate\Support\Facades\Hash::check($data['code'], $hashedCode)) {
                    $valid = true;
                    // Remove used backup code
                    $user->mfa_backup_codes = array_values(array_filter($user->mfa_backup_codes, fn ($c) => $c !== $hashedCode));
                    $user->save();
                    break;
                }
            }
        }

        if (! $valid) {
            $request->session()->put('mfa_pending_user', $userId);
            return back()->withErrors(['code' => 'Invalid code.']);
        }

        // MFA verified - sign in the user
        Auth::login($user, true);
        $request->session()->regenerate();
        $request->session()->put('mfa_verified', true);
        $request->session()->flash('admin_dashboard_entrance', true);

        $redirect = redirect()->intended(route('admin.dashboard'))->getTargetUrl();

        if ($request->expectsJson()) {
            return response()->json(['redirect' => $redirect]);
        }

        return redirect()->intended(route('admin.dashboard'));
    }
}
