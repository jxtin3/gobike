<?php

use App\Models\Donation;
use App\Models\ReplicationRequest;
use App\Services\ChatbotSiteGuide;
use Illuminate\Support\Facades\Http;

function switchDonationPayload(): array
{
    return [
        'currency' => 'USD', 'amount' => 25, 'platform_fee' => 1.88, 'frequency' => 'once', 'payment_method' => 'gcash',
        'donor_type' => 'individual', 'first_name' => 'Test', 'last_name' => 'Donor', 'email' => 'donor@example.com',
        'address1' => '123 Test St.', 'city' => 'Test City', 'postcode' => '1000', 'state' => 'Test State', 'country' => 'PH',
    ];
}

function switchInquiryPayload(array $overrides = []): array
{
    return array_merge([
        'organization' => 'SK of Barangay Poblacion', 'organization_type' => 'sk', 'contact_name' => 'Juan Dela Cruz',
        'position' => 'SK Chairperson', 'email' => 'sk@example.com', 'phone' => '+63 917 123 4567',
        'municipality' => 'Bugallon', 'province' => 'Pangasinan', 'trainees' => 16,
        'preferred_date' => now()->addMonth()->toDateString(), 'payment' => 'later', 'agree' => '1',
    ], $overrides);
}

// Online payments are switched off on the live site until PayPal / PayMongo are set up (config/payments.php).
beforeEach(function () {
    config(['payments.online' => false]);
    Http::fake(); // Nothing may reach PayPal or PayMongo.
});

it('shows "opening soon" on the Donate page instead of the form', function () {
    $this->get('/donate')
        ->assertOk()
        ->assertSee('Online donations are opening soon')
        ->assertSee('gobike@pydn.org')
        ->assertDontSee('I&#039;d like to make this donation', false)
        ->assertDontSee('id="donate-submit-btn"', false);
});

it('refuses a donation checkout and saves nothing', function () {
    $this->postJson('/donate/checkout', switchDonationPayload())
        ->assertStatus(503)
        ->assertJsonPath('message', 'Online donations are not open yet. Please contact us to give.');

    expect(Donation::count())->toBe(0);
    Http::assertNothingSent();
});

it('takes chapter inquiries without the pay-now choice', function () {
    $this->get('/replicate-go-bike')
        ->assertOk()
        ->assertDontSee('Send inquiry and pay now')
        ->assertDontSee('pay early online');

    $this->post(route('replicate.inquiry.store'), switchInquiryPayload(['payment' => 'online']))
        ->assertSessionHasErrors('payment');

    $this->post(route('replicate.inquiry.store'), switchInquiryPayload())
        ->assertRedirect()
        ->assertSessionHas('replication_success');

    expect(ReplicationRequest::count())->toBe(1);
    Http::assertNothingSent();
});

it('does not send an inquiry to PayMongo from its status page', function () {
    $inquiry = ReplicationRequest::create(collect(switchInquiryPayload())->except(['payment', 'agree'])->all() + ['amount_php' => 32000]);

    $this->get(route('replicate.inquiry.pay', $inquiry))
        ->assertRedirect(route('replicate.inquiry.show', $inquiry));

    $this->get(route('replicate.inquiry.show', $inquiry))
        ->assertOk()
        ->assertSee('Your inquiry is saved')
        ->assertDontSee(route('replicate.inquiry.pay', $inquiry));

    Http::assertNothingSent();
});

it('tells GoBot that online payments are not open yet', function () {
    expect(app(ChatbotSiteGuide::class)->text())
        ->toContain('Online donations are not open yet', 'paid by check to PRI')
        ->not->toContain('Payment Details: PayPal', 'paid early online');
});

it('turns everything back on with one setting', function () {
    config(['payments.online' => true]);

    $this->get('/donate')->assertSee('id="donate-submit-btn"', false)->assertDontSee('Online donations are opening soon');
    $this->get('/replicate-go-bike')->assertSee('Send inquiry and pay now');
    expect(app(ChatbotSiteGuide::class)->text())->toContain('Payment Details: PayPal');
});
