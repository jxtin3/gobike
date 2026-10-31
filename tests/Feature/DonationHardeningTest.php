<?php

use App\Models\Donation;
use App\Services\PayMongoDonationService;
use App\Services\PayPalDonationService;

function validCheckoutPayload(array $overrides = []): array
{
    return array_merge([
        'currency' => 'USD',
        'amount' => 25,
        'platform_fee' => 1.88,
        'frequency' => 'once',
        'payment_method' => 'gcash',
        'donor_type' => 'individual',
        'first_name' => 'Test',
        'last_name' => 'Donor',
        'email' => 'donor@example.com',
        'address1' => '123 Test St.',
        'city' => 'Test City',
        'postcode' => '1000',
        'state' => 'Test State',
        'country' => 'PH',
    ], $overrides);
}

beforeEach(function () {
    // Mocks stop the real services from calling PayPal / PayMongo.
    $this->mock(PayPalDonationService::class);
    $this->paymongo = $this->mock(PayMongoDonationService::class);
});

it('rejects a donation amount above the maximum', function () {
    $this->paymongo->shouldReceive('createCheckoutSession')
        ->andReturn(['session_id' => 'cs_1', 'checkout_url' => 'https://example.test/pay']);

    $this->postJson(route('donate.checkout'), validCheckoutPayload(['amount' => 10001]))
        ->assertJsonValidationErrors('amount');
});

it('rejects an absurd platform fee', function () {
    $this->paymongo->shouldReceive('createCheckoutSession')
        ->andReturn(['session_id' => 'cs_1', 'checkout_url' => 'https://example.test/pay']);

    $this->postJson(route('donate.checkout'), validCheckoutPayload(['platform_fee' => 5000]))
        ->assertJsonValidationErrors('platform_fee');
});

it('hides payment provider errors from the visitor', function () {
    $this->paymongo->shouldReceive('createCheckoutSession')
        ->andThrow(new RuntimeException('provider-secret-detail'));

    $response = $this->postJson(route('donate.checkout'), validCheckoutPayload())
        ->assertStatus(422);

    expect($response->json('message'))->not->toContain('provider-secret-detail');
});

it('rate limits the checkout endpoint', function () {
    for ($i = 0; $i < 10; $i++) {
        $this->postJson(route('donate.checkout'), [])->assertStatus(422);
    }

    $this->postJson(route('donate.checkout'), [])->assertStatus(429);
});

it('hides payment provider errors on retry', function () {
    $this->paymongo->shouldReceive('createCheckoutSession')
        ->andThrow(new RuntimeException('provider-secret-detail'));

    $donation = Donation::create([
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
        'paymongo_session_id' => 'cs_old',
    ]);

    $this->get(route('donate.retry', $donation))
        ->assertRedirect(route('donate.cancel', $donation));

    expect(session('error'))->not->toContain('provider-secret-detail');
});