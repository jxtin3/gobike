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
        $dev = implode(' ', $this->devServer());
        $withDev = fn (string $directive) => trim($directive.' '.$dev);

        $mediaOrigin = trim((string) config('security.media_origin', ''));

        $imgSrc = "img-src 'self' data: blob: https://*.tile.openstreetmap.org https://tile.openstreetmap.org";
        if ($mediaOrigin !== '') {
            $imgSrc .= ' '.$mediaOrigin;
        }

        $directives = [
            "default-src 'self'",
            // Alpine.js and a few inline admin scripts need these two.
            $withDev("script-src 'self' 'unsafe-inline' 'unsafe-eval'"),
            $withDev("style-src 'self' 'unsafe-inline'"),
            // Map tiles come from OpenStreetMap.
            $withDev($imgSrc),
        ];

        if ($mediaOrigin !== '') {
            $directives[] = $withDev("media-src 'self' {$mediaOrigin} blob: data:");
        }

        $directives = array_merge($directives, [
            $withDev("font-src 'self' data:"),
            // The site only talks to itself (donations, chatbot, admin map).
            $withDev("connect-src 'self'"),
            // The contact page shows an embedded Google map.
            'frame-src https://www.google.com',
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ]);

        return implode('; ', $directives);
    }

    /**
     * While `npm run dev` is running locally, Vite serves assets from its own address.
     * This exception exists only in the local environment, never in production.
     */
    private function devServer(): array
    {
        if (! app()->environment('local') || ! is_file(public_path('hot'))) {
            return [];
        }

        $url = rtrim(trim((string) file_get_contents(public_path('hot'))), '/');

        return $url === '' ? [] : [$url, preg_replace('/^http/', 'ws', $url)];
    }
    
}