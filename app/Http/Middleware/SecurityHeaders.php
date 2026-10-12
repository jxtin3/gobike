<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        $mode = config('security.csp_mode', 'report-only');

        if ($mode === 'enforce') {
            $response->headers->set('Content-Security-Policy', $this->policy());
        } elseif ($mode === 'report-only') {
            $response->headers->set('Content-Security-Policy-Report-Only', $this->policy());
        }

        // Tell browsers to use HTTPS only. Sent only over HTTPS, and only in production.
        if ($request->isSecure() && app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }

    private function policy(): string
    {
        return implode('; ', [
            "default-src 'self'",
            // Alpine.js and a few inline admin scripts need these two.
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline'",
            // Map tiles come from OpenStreetMap.
            "img-src 'self' data: blob: https://*.tile.openstreetmap.org https://tile.openstreetmap.org",
            "font-src 'self' data:",
            // The site only talks to itself (donations, chatbot, admin map).
            "connect-src 'self'",
            // The contact page shows an embedded Google map.
            'frame-src https://www.google.com',
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ]);
    }
}