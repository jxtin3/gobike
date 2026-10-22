<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next)
    {
        // Not logged in -> go to the login page
        if (! Auth::check()) {
            if ($request->expectsJson()) {
                abort(401, 'Unauthenticated.');
            }

            return redirect()->guest(route('login'));
        }

        // Logged in but not an admin -> forbidden
        if (! Auth::user()->is_admin) {
            abort(403, 'Access denied.');
        }

        // An admin deactivated after signing in loses access on their next request.
        if ((Auth::user()->status ?? 'Active') !== 'Active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                abort(401, 'Unauthenticated.');
            }

            return redirect()->route('login')->withErrors(['email' => 'Your admin account is not active.']);
        }

        return $next($request);
    }
}
