<?php

use App\Models\Donation;
use App\Services\PayPalDonationService;

function makeWebhookDonation(): Donation
{
    return Donation::create([
        'donor_type' => 'individual',
        'first_name' => 'Test',
        'last_name' => 'Donor',
        'email' => 'donor@example.com',
        'address1' => '123 Test St.',
        'city' => 'Test City',
        'postcode' => '1000',
        'state' => 'Test State',
        'country' => 'PH',
        'amount_usd' => 25,
        'amount_php' => 1450,
        'platform_fee_usd' => 0,
        'frequency' => 'once',
        'payment_method' => 'gcash',
        'status' => 'pending',
    ]);
}

function paymongoPaidBody(Donation $donation): string
{
    return json_encode([
        'data' => ['attributes' => [
            'type' => 'checkout_session.payment.paid',
            'data' => ['attributes' => [
                'metadata' => ['donation_uuid' => $donation->uuid],
                'payments' => [['id' => 'pay_test_123']],
            ]],
        ]],
    ]);
}

function postPaymongoWebhook($test, string $body, string $signature)
{
    return $test->call('POST', '/webhooks/paymongo', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_PAYMONGO_SIGNATURE' => $signature,
    ], $body);
}

it('rejects PayMongo webhooks when no secret is configured', function () {
    config(['services.paymongo.webhook_secret' => '']);
    $donation = makeWebhookDonation();

    postPaymongoWebhook($this, paymongoPaidBody($donation), 't=1,v1=anything')
        ->assertStatus(400);

    expect($donation->fresh()->status)->toBe('pending');
});

it('rejects PayMongo webhooks with a wrong signature', function () {
    config(['services.paymongo.webhook_secret' => 'whsk_test_secret']);
    $donation = makeWebhookDonation();

    postPaymongoWebhook($this, paymongoPaidBody($donation), 't=1700000000,v1=deadbeef')
        ->assertStatus(400);

    expect($donation->fresh()->status)->toBe('pending');
});

it('accepts a correctly signed PayMongo webhook and marks the donation paid', function () {
    $secret = 'whsk_test_secret';
    config(['services.paymongo.webhook_secret' => $secret]);
    $donation = makeWebhookDonation();

    $body = paymongoPaidBody($donation);
    $timestamp = (string) time();
    $signature = hash_hmac('sha256', $timestamp.'.'.$body, $secret);

    postPaymongoWebhook($this, $body, "t={$timestamp},v1={$signature}")
        ->assertOk();

    expect($donation->fresh()->status)->toBe('paid');
});

it('rejects PayPal webhooks when no webhook id is configured', function () {
    // partialMock avoids the real constructor, which calls PayPal's API.
    $this->partialMock(PayPalDonationService::class);
    config(['paypal.webhook_id' => '']);

    $this->call('POST', '/webhooks/paypal', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode(['event_type' => 'PAYMENT.CAPTURE.COMPLETED']))
        ->assertStatus(400);
});