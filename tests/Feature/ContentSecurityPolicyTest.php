<?php

it('sends the policy in report-only mode', function () {
    config(['security.csp_mode' => 'report-only']);

    $response = $this->get('/')->assertOk();

    expect($response->headers->get('Content-Security-Policy-Report-Only'))->toContain("default-src 'self'");
    $response->assertHeaderMissing('Content-Security-Policy');
});

it('enforces the policy when switched on', function () {
    config(['security.csp_mode' => 'enforce']);

    $response = $this->get('/')->assertOk();

    expect($response->headers->get('Content-Security-Policy'))->toContain("object-src 'none'");
    $response->assertHeaderMissing('Content-Security-Policy-Report-Only');
});

it('can be turned off', function () {
    config(['security.csp_mode' => 'off']);

    $this->get('/')
        ->assertOk()
        ->assertHeaderMissing('Content-Security-Policy')
        ->assertHeaderMissing('Content-Security-Policy-Report-Only');
});

it('allows only the outside sites the project really uses', function () {
    config(['security.csp_mode' => 'enforce']);

    $policy = $this->get('/')->headers->get('Content-Security-Policy');

    expect($policy)
        ->toContain('https://*.tile.openstreetmap.org')
        ->toContain('frame-src https://www.google.com')
        ->toContain("connect-src 'self'")
        ->not->toContain('unpkg.com');
});

it('allows the Vite dev server only locally while it is running', function () {
    $hot = public_path('hot');

    if (file_exists($hot)) {
        $this->markTestSkipped('The Vite dev server is running; stop it to run this test.');
    }

    config(['security.csp_mode' => 'enforce']);

    try {
        file_put_contents($hot, 'http://[::1]:5173');

        app()->detectEnvironment(fn () => 'local');
        $local = $this->get('/')->headers->get('Content-Security-Policy');

        app()->detectEnvironment(fn () => 'production');
        $production = $this->get('/')->headers->get('Content-Security-Policy');
    } finally {
        @unlink($hot);
    }

    expect($local)->toContain('http://[::1]:5173')->toContain('ws://[::1]:5173');
    expect($production)->not->toContain('5173');
});

it('allows media bucket origin in img-src and media-src when MEDIA_CSP_ORIGIN is set', function () {
    config([
        'security.csp_mode' => 'enforce',
        'security.media_origin' => 'https://bucket.example.com',
    ]);

    $policy = $this->get('/')->headers->get('Content-Security-Policy');

    expect($policy)
        ->toContain("img-src 'self' data: blob: https://*.tile.openstreetmap.org https://tile.openstreetmap.org https://bucket.example.com")
        ->toContain("media-src 'self' https://bucket.example.com blob: data:");
});

it('omits media bucket origin when MEDIA_CSP_ORIGIN is unset', function () {
    config([
        'security.csp_mode' => 'enforce',
        'security.media_origin' => '',
    ]);

    $policy = $this->get('/')->headers->get('Content-Security-Policy');

    expect($policy)
        ->not->toContain('media-src')
        ->not->toContain('https://bucket.example.com');
});