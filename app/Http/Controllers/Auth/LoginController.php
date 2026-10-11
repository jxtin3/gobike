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
}
