<?php

use App\Models\Donation;
use App\Models\User;
use App\Services\PayPalDonationService;
use Illuminate\Support\Facades\Http;

function makeMonthlyDonation(array $overrides = []): Donation
{
    return Donation::create(array_merge([
        'donor_type' => 'individual',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'email' => 'maria@example.com',
        'address1' => '123 Test St.',
        'city' => 'Lingayen',
        'postcode' => '2401',
        'state' => 'Pangasinan',
        'country' => 'PH',
        'amount_usd' => 25,
        'platform_fee_usd' => 0,
        'frequency' => 'monthly',
        'payment_method' => 'paypal',
        'status' => 'paid',
        'paypal_subscription_id' => 'I-MONTHLY-1',
        'subscription_status' => 'active',
    ], $overrides));
}

function trustPaypalWebhooks(): void
{
    app()->instance(PayPalDonationService::class, Mockery::mock(PayPalDonationService::class)->makePartial()
        ->shouldReceive('verifyWebhookLocally')->andReturn(true)->getMock());
}

it('records each monthly charge once from the PayPal webhook', function () {
    trustPaypalWebhooks();
    $donation = makeMonthlyDonation();
    $sale = [
        'event_type' => 'PAYMENT.SALE.COMPLETED',
        'resource' => [
            'id' => 'SALE-1',
            'billing_agreement_id' => 'I-MONTHLY-1',
            'amount' => ['total' => '25.00', 'currency' => 'USD'],
            'create_time' => '2026-11-22T08:00:00Z',
        ],
    ];

    $this->postJson('/webhooks/paypal', $sale)->assertOk();
    $this->postJson('/webhooks/paypal', $sale)->assertOk(); // PayPal may resend events

    expect($donation->payments()->count())->toBe(1)
        ->and((float) $donation->payments()->first()->amount)->toBe(25.0)
        ->and($donation->fresh()->subscription_status)->toBe('active');
});

it('tracks cancelled and paused monthly donations from PayPal', function () {
    trustPaypalWebhooks();
    $donation = makeMonthlyDonation();

    $this->postJson('/webhooks/paypal', ['event_type' => 'BILLING.SUBSCRIPTION.SUSPENDED', 'resource' => ['id' => 'I-MONTHLY-1']])->assertOk();
    expect($donation->fresh()->subscription_status)->toBe('suspended');

    $this->postJson('/webhooks/paypal', ['event_type' => 'BILLING.SUBSCRIPTION.CANCELLED', 'resource' => ['id' => 'I-MONTHLY-1']])->assertOk();
    expect($donation->fresh())
        ->subscription_status->toBe('cancelled')
        ->cancelled_at->not->toBeNull();
});

it('shows the donor their monthly donation with the next charge date', function () {
    $donation = makeMonthlyDonation();
    $this->partialMock(PayPalDonationService::class, function ($mock) {
        $mock->shouldReceive('getSubscriptionDetails')->andReturn([
            'status' => 'ACTIVE',
            'billing_info' => ['next_billing_time' => '2026-11-22T10:00:00Z'],
        ]);
    });

    $this->get(route('donate.monthly', $donation))
        ->assertOk()
        ->assertSee($donation->reference)
        ->assertSee('November 22, 2026')
        ->assertSee('Cancel monthly donation');
});

it('picks up a cancellation made in PayPal when the donor opens the page', function () {
    $donation = makeMonthlyDonation();
    $this->partialMock(PayPalDonationService::class, function ($mock) {
        $mock->shouldReceive('getSubscriptionDetails')->andReturn(['status' => 'CANCELLED']);
    });

    $this->get(route('donate.monthly', $donation))
        ->assertOk()
        ->assertSee('has stopped')
        ->assertDontSee('Yes, cancel it');

    expect($donation->fresh()->subscription_status)->toBe('cancelled');
});

it('lets donors cancel their monthly donation themselves', function () {
    $donation = makeMonthlyDonation();
    $this->partialMock(PayPalDonationService::class, function ($mock) {
        $mock->shouldReceive('cancelSubscription')->once()->with('I-MONTHLY-1', \Mockery::type('string'));
    });

    $this->post(route('donate.monthly.cancel', $donation))
        ->assertRedirect(route('donate.monthly', $donation))
        ->assertSessionHas('success');

    expect($donation->fresh())
        ->subscription_status->toBe('cancelled')
        ->cancelled_at->not->toBeNull();

    // A second attempt does not call PayPal again.
    $this->post(route('donate.monthly.cancel', $donation))->assertRedirect(route('donate.monthly', $donation));
});

it('keeps the monthly donation active when PayPal cannot cancel it', function () {
    $donation = makeMonthlyDonation();
    $this->partialMock(PayPalDonationService::class, function ($mock) {
        $mock->shouldReceive('cancelSubscription')->andThrow(new RuntimeException('PayPal down'));
    });

    $this->post(route('donate.monthly.cancel', $donation))->assertSessionHas('error');

    expect($donation->fresh()->subscription_status)->toBe('active');
});

it('has no monthly management page for one-time donations', function () {
    $donation = makeMonthlyDonation(['frequency' => 'once', 'paypal_subscription_id' => null, 'subscription_status' => null]);

    $this->get(route('donate.monthly', $donation))->assertNotFound();
    $this->post(route('donate.monthly.cancel', $donation))->assertNotFound();
});

it('shows monthly donors how to manage their donation after paying', function () {
    $donation = makeMonthlyDonation();

    $this->get(route('donate.success', $donation))
        ->assertOk()
        ->assertSee('Your monthly donation of')
        ->assertSee(route('donate.monthly', $donation), false);
});

it('lets admins cancel a monthly donation', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $donation = makeMonthlyDonation();
    $this->partialMock(PayPalDonationService::class, function ($mock) {
        $mock->shouldReceive('cancelSubscription')->once();
    });

    $this->actingAs($admin)
        ->get(route('admin.operations.donations.index'))
        ->assertOk()
        ->assertSee('Active monthly donors')
        ->assertSee(route('admin.operations.donations.cancel-subscription', $donation), false);

    $this->actingAs($admin)
        ->post(route('admin.operations.donations.cancel-subscription', $donation))
        ->assertSessionHas('success');

    expect($donation->fresh()->subscription_status)->toBe('cancelled');
});

it('treats an already-cancelled PayPal subscription as cancelled', function () {
    config([
        'paypal.mode' => 'sandbox',
        'paypal.sandbox.client_id' => 'id',
        'paypal.sandbox.client_secret' => 'secret',
    ]);
    Http::fake([
        'api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'token']),
        'api-m.sandbox.paypal.com/v1/billing/subscriptions/*/cancel' => Http::response([
            'name' => 'UNPROCESSABLE_ENTITY',
            'details' => [['issue' => 'SUBSCRIPTION_STATUS_INVALID']],
        ], 422),
    ]);

    app(PayPalDonationService::class)->cancelSubscription('I-MONTHLY-1', 'test');

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/v1/billing/subscriptions/I-MONTHLY-1/cancel'));
});

it('lets admins delete a one-time donation', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $donation = makeMonthlyDonation(['frequency' => 'once', 'paypal_subscription_id' => null, 'subscription_status' => null]);

    $this->actingAs($admin)
        ->get(route('admin.operations.donations.index'))
        ->assertOk()
        ->assertSee('Delete donation')
        ->assertSee(route('admin.operations.donations.destroy', $donation), false);

    $this->actingAs($admin)
        ->delete(route('admin.operations.donations.destroy', $donation))
        ->assertSessionHas('success');

    expect(Donation::find($donation->id))->toBeNull();
});

it('will not delete a monthly donation that PayPal can still charge', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $donation = makeMonthlyDonation();
    $this->partialMock(PayPalDonationService::class, function ($mock) {
        $mock->shouldReceive('subscriptionBillingState')->once()->with('I-MONTHLY-1')->andReturn('billing');
    });

    $this->actingAs($admin)
        ->delete(route('admin.operations.donations.destroy', $donation))
        ->assertSessionHas('error');

    expect(Donation::find($donation->id))->not->toBeNull();
});

it('deletes a monthly donation once PayPal has stopped it or no longer knows it', function (string $state) {
    $admin = User::factory()->create(['is_admin' => true]);
    $donation = makeMonthlyDonation();
    $this->partialMock(PayPalDonationService::class, function ($mock) use ($state) {
        $mock->shouldReceive('subscriptionBillingState')->once()->andReturn($state);
    });

    $this->actingAs($admin)
        ->delete(route('admin.operations.donations.destroy', $donation))
        ->assertSessionHas('success');

    expect(Donation::find($donation->id))->toBeNull();
})->with(['stopped', 'not_found']);

it('will not delete a monthly donation when PayPal cannot be reached', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $donation = makeMonthlyDonation();
    $this->partialMock(PayPalDonationService::class, function ($mock) {
        $mock->shouldReceive('subscriptionBillingState')->andThrow(new RuntimeException('PayPal down'));
    });

    $this->actingAs($admin)
        ->delete(route('admin.operations.donations.destroy', $donation))
        ->assertSessionHas('error');

    expect(Donation::find($donation->id))->not->toBeNull();
});

it('only lets admins delete donations', function () {
    $donation = makeMonthlyDonation(['frequency' => 'once', 'paypal_subscription_id' => null]);

    $this->delete(route('admin.operations.donations.destroy', $donation))->assertRedirect();

    expect(Donation::find($donation->id))->not->toBeNull();
});
