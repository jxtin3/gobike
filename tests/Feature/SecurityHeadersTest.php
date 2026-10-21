<?php

it('sends basic security headers', function () {
    $this->get('/')
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
        ->assertHeaderMissing('Strict-Transport-Security');
});

it('sends HSTS only for https requests in production', function () {
    app()->detectEnvironment(fn () => 'production');

    $this->get('http://localhost/')->assertHeaderMissing('Strict-Transport-Security');
    $this->get('https://localhost/')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
});

it('handles media origin in CSP when set or unset', function () {
    config([
        'security.csp_mode' => 'enforce',
        'security.media_origin' => '',
    ]);

    $response = $this->get('/')->assertOk();
    $csp = $response->headers->get('Content-Security-Policy');
    expect($csp)
        ->not->toContain('media-src')
        ->not->toContain('https://bucket.example.com');

    config(['security.media_origin' => 'https://bucket.example.com']);

    $responseWithOrigin = $this->get('/')->assertOk();
    $cspWithOrigin = $responseWithOrigin->headers->get('Content-Security-Policy');
    expect($cspWithOrigin)
        ->toContain('https://bucket.example.com')
        ->toContain("media-src 'self' https://bucket.example.com");
});