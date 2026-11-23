<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureMfaVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Not logged in -> the 'admin' middleware will handle redirect
        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Only admins need MFA in the admin area
        if (! $user->is_admin) {
            return $next($request);
        }

        // MFA not enabled -> all good
        if (! $user->hasMfaEnabled()) {
            return $next($request);
        }

        // MFA enabled but already verified in this session
        if ($request->session()->get('mfa_verified')) {
            return $next($request);
        }

        // Store intended URL and redirect to MFA verification
        if (! $request->is('mfa/verify*') && ! $request->is('mfa/disable')) {
            $request->session()->put('url.intended', $request->url());
        }

        return redirect()->route('mfa.verify');
    }
}