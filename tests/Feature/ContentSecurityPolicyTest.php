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