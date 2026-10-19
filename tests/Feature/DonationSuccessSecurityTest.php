<?php

use App\Models\Donation;
use App\Services\PayPalDonationService;

function makePendingPaypalDonation(array $overrides = []): Donation
{
    return Donation::create(array_merge([
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
        'payment_method' => 'paypal',
        'status' => 'pending',
    ], $overrides));
}

it('checks the stored subscription, not the one in the URL', function () {
    $donation = makePendingPaypalDonation([
        'frequency' => 'monthly',
        'paypal_subscription_id' => 'I-OWN-SUBSCRIPTION',
    ]);

    $this->mock(PayPalDonationService::class, function ($mock) {
        $mock->shouldReceive('getSubscriptionDetails')
            ->once()
            ->with('I-OWN-SUBSCRIPTION')
            ->andReturn(['id' => 'I-OWN-SUBSCRIPTION', 'status' => 'APPROVAL_PENDING']);
        $mock->shouldReceive('isSubscriptionActive')->once()->andReturn(false);
    });

    $this->get(route('donate.success', $donation).'?subscription_id=I-SOMEONE-ELSE')
        ->assertRedirect(route('donate.cancel', $donation));

    expect($donation->fresh()->status)->toBe('pending');
});

it('captures the stored order, not the token in the URL', function () {
    $donation = makePendingPaypalDonation([
        'paypal_order_id' => 'ORDER-OWN',
    ]);

    $this->mock(PayPalDonationService::class, function ($mock) {
        $mock->shouldReceive('captureOrder')
            ->once()
            ->with('ORDER-OWN')
            ->andReturn(['id' => 'ORDER-OWN', 'status' => 'PENDING']);
        $mock->shouldReceive('isCaptureSuccessful')->once()->andReturn(false);
    });

    $this->get(route('donate.success', $donation).'?token=ORDER-SOMEONE-ELSE')
        ->assertRedirect(route('donate.cancel', $donation));

    expect($donation->fresh()->status)->toBe('pending');
});

it('marks a monthly donation paid when its own subscription is active', function () {
    $donation = makePendingPaypalDonation([
        'frequency' => 'monthly',
        'paypal_subscription_id' => 'I-OWN-SUBSCRIPTION',
    ]);

    $this->mock(PayPalDonationService::class, function ($mock) {
        $mock->shouldReceive('getSubscriptionDetails')
            ->once()
            ->with('I-OWN-SUBSCRIPTION')
            ->andReturn(['id' => 'I-OWN-SUBSCRIPTION', 'status' => 'ACTIVE']);
        $mock->shouldReceive('isSubscriptionActive')->once()->andReturn(true);
    });

    $this->get(route('donate.success', $donation).'?subscription_id=ignored-value')
        ->assertOk();

    expect($donation->fresh()->status)->toBe('paid');
});