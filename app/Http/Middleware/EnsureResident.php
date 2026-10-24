<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Residents are stored with the role "User". */
class EnsureResident
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user?->role !== 'User' || $user->is_admin) {
            abort(403, 'Only residents can use this.');
        }

        if (($user->status ?? 'Active') !== 'Active') {
            abort(403, 'Your account is not active. Please contact your admin.');
        }

        return $next($request);
    }
}
