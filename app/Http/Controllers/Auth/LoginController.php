<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Mfa;
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

        // The password is checked without signing in: an admin with two-step login is signed
        // in only after the code (verifyMfa), so no session or "Remember me" cookie exists before it.
        $user = Auth::getProvider()->retrieveByCredentials(['email' => $credentials['email']]);

        if ($user && Auth::getProvider()->validateCredentials($user, $credentials)) {
            RateLimiter::clear($emailKey);

            if (! $user->is_admin || ($user->status ?? 'Active') !== 'Active') {
                $message = 'You do not have access to the admin area.';

                if ($request->expectsJson()) {
                    return response()->json(['message' => $message], 403);
                }

                return back()->withErrors(['email' => $message]);
            }

            $request->session()->regenerate();
            $remember = $request->boolean('remember');

            if ($user->hasMfaEnabled()) {
                $request->session()->put('mfa_pending_user', $user->id);
                $request->session()->put('mfa_remember', $remember);

                if ($request->expectsJson()) {
                    return response()->json(['redirect' => route('mfa.verify'), 'mfa_required' => true]);
                }

                return redirect()->route('mfa.verify');
            }

            // Admins must have two-step login: set it up before anything else (EnsureMfaVerified).
            Auth::login($user, $remember);
            $request->session()->put('mfa_setup_required', true);

            if ($request->expectsJson()) {
                return response()->json(['redirect' => route('admin.mfa.setup')]);
            }

            return redirect()->route('admin.mfa.setup');
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
     * The second step of an admin sign-in: the authenticator code, or a backup code.
     * Five wrong codes lock this sign-in for 5 minutes.
     */
    public function verifyMfa(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20'],
        ]);

        $userId = $request->session()->get('mfa_pending_user');
        $user = $userId ? User::find($userId) : null;

        if (! $user || ! $user->hasMfaEnabled() || ! $user->is_admin || ($user->status ?? 'Active') !== 'Active') {
            $request->session()->forget(['mfa_pending_user', 'mfa_remember']);

            return redirect()->route('login')->withErrors(['email' => 'Please sign in again.']);
        }

        $key = 'mfa:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $message = 'Too many wrong codes. Try again in '.RateLimiter::availableIn($key).' seconds.';

            return $request->expectsJson()
                ? response()->json(['message' => $message, 'errors' => ['code' => [$message]]], 429)
                : back()->withErrors(['code' => $message]);
        }

        if (! Mfa::verify($user, $data['code'])) {
            RateLimiter::hit($key, 300);
            $message = 'That code is wrong. Try the newest code in your authenticator app.';

            return $request->expectsJson()
                ? response()->json(['message' => $message, 'errors' => ['code' => [$message]]], 422)
                : back()->withErrors(['code' => $message]);
        }

        RateLimiter::clear($key);
        $remember = (bool) $request->session()->pull('mfa_remember', false);
        $request->session()->forget('mfa_pending_user');

        Auth::login($user, $remember);
        $request->session()->regenerate();
        $request->session()->put('mfa_verified', true);
        $request->session()->flash('admin_dashboard_entrance', true);

        $redirect = redirect()->intended(route('admin.dashboard'));

        if ($request->expectsJson()) {
            return response()->json(['redirect' => $redirect->getTargetUrl()]);
        }

        return $redirect;
    }
}
