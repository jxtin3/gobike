<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Railway runs behind a load balancer / reverse proxy. Trust all proxies so
        // X-Forwarded-* headers (like client IP and HTTPS scheme) are properly resolved.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'gobiker' => \App\Http\Middleware\EnsureGoBiker::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
        ]);

        // Set by the cookie banner in the browser (plain "all" / "essential"), so it isn't encrypted.
        $middleware->encryptCookies(except: [
            'gobike_cookie_consent',
            // Admin sidebar state (collapsed rail, closed groups), written by resources/js/admin/sidebar.js.
            'gobike_admin_rail',
            'gobike_admin_closed',
        ]);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->expectsJson()
                || $request->is('api/*')
                || $request->is('chatbot/*')
                || $request->is('contact')
                || $request->is('volunteer'),
        );
    })->create();