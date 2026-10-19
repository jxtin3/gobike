<?php

use App\Services\PayPalDonationService;

it('does not contact PayPal just by creating the service', function () {
    $service = new PayPalDonationService;

    $client = new ReflectionProperty($service, 'client');

    expect($client->isInitialized($service) && $client->getValue($service) !== null)->toBeFalse();
});

it('rejects PayPal webhooks that point at a non-PayPal certificate', function () {
    // partialMock avoids the real constructor.
    $this->partialMock(PayPalDonationService::class);
    config(['paypal.webhook_id' => 'WH-TEST-123']);

    $this->call('POST', '/webhooks/paypal', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_PAYPAL_CERT_URL' => 'https://evil.example.com/cert.pem',
        'HTTP_PAYPAL_TRANSMISSION_ID' => '1',
        'HTTP_PAYPAL_TRANSMISSION_TIME' => '2026-01-01T00:00:00Z',
        'HTTP_PAYPAL_TRANSMISSION_SIG' => 'abc',
        'HTTP_PAYPAL_AUTH_ALGO' => 'SHA256withRSA',
    ], '{}')->assertStatus(400);
});

it('rejects PayPal webhooks that are missing signature headers', function () {
    $this->partialMock(PayPalDonationService::class);
    config(['paypal.webhook_id' => 'WH-TEST-123']);

    $this->call('POST', '/webhooks/paypal', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_PAYPAL_CERT_URL' => 'https://api.paypal.com/v1/notifications/certs/CERT-1',
    ], '{}')->assertStatus(400);
});