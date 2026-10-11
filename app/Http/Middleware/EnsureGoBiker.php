<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureGoBiker
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user?->role !== 'GoBiker') {
            abort(403, 'Only Go Bikers can share their location.');
        }

        if (($user->status ?? 'Active') !== 'Active') {
            abort(403, 'Your account is not active. Please contact your admin.');
        }

        return $next($request);
    }

    
}