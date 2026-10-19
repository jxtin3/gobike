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
        // returns the proxy's address and $request->isSecure() is false.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'gobiker' => \App\Http\Middleware\EnsureGoBiker::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
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