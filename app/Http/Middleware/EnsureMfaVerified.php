<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admins must use two-step login (App\Support\Mfa).
 *
 * - MFA on: the session must have passed the code. "Remember me" counts, since the code was
 *   entered when that cookie was made. Any other session is signed out and asked for the code.
 * - MFA off, right after a password sign-in: every admin page sends to the setup page until it
 *   is done.
 */
class EnsureMfaVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check() || ! Auth::user()->is_admin) {
            return $next($request);
        }

        $user = Auth::user();
        $session = $request->session();

        if ($user->hasMfaEnabled()) {
            if ($session->get('mfa_verified')) {
                return $next($request);
            }

            if (Auth::viaRemember()) {
                $session->put('mfa_verified', true);

                return $next($request);
            }

            // Signed in without the code (MFA was turned on after this session began).
            if ($request->isMethod('GET') && ! $request->expectsJson()) {
                $session->put('url.intended', $request->fullUrl());
            }
            Auth::guard('web')->logout();
            $session->regenerate();
            $session->put('mfa_pending_user', $user->id);
            $session->put('mfa_remember', false);

            return redirect()->route('mfa.verify');
        }

        if (Auth::viaRemember()) {
            $session->put('mfa_setup_required', true);
        }

        if ($session->get('mfa_setup_required') && ! $request->routeIs('admin.mfa.setup', 'admin.mfa.verify')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Set up two-step login first.', 'redirect' => route('admin.mfa.setup')], 403);
            }

            return redirect()->route('admin.mfa.setup');
        }

        return $next($request);
    }
}
