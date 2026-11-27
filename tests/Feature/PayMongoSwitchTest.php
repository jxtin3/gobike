<?php

use App\Models\Donation;
use App\Models\ReplicationRequest;
use App\Services\PayPalDonationService;
use Illuminate\Support\Facades\Http;

function payMongoOffDonationPayload(array $overrides = []): array
{
    return array_merge([
        'currency' => 'USD',
        'amount' => 25,
        'platform_fee' => 0,
        'frequency' => 'once',
        'payment_method' => 'paypal',
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

function payMongoOffInquiryPayload(array $overrides = []): array
{
    return array_merge([
        'organization' => 'SK of Barangay Poblacion',
        'organization_type' => 'sk',
        'contact_name' => 'Juan Dela Cruz',
        'position' => 'SK Chairperson',
        'email' => 'sk@example.com',
        'phone' => '+63 917 123 4567',
        'municipality' => 'Bugallon',
        'province' => 'Pangasinan',
        'trainees' => 16,
        'preferred_date' => now()->addMonth()->toDateString(),
        'message' => 'We would like to start a Go Bike chapter.',
        'payment' => 'online',
        'agree' => '1',
    ], $overrides);
}

function makePayMongoOffInquiry(array $overrides = []): ReplicationRequest
{
    return ReplicationRequest::create(array_merge(
        collect(payMongoOffInquiryPayload())->except(['payment', 'agree'])->all(),
        ['amount_php' => 32000],
        $overrides,
    ));
}

function payMongoOffCaptureResponse(string $captureId = 'CAPTURE-1'): array
{
    return [
        'status' => 'COMPLETED',
        'purchase_units' => [['payments' => ['captures' => [['id' => $captureId]]]]],
    ];
}

beforeEach(function () {
    config(['payments.online' => true, 'payments.paymongo' => false]);
});

it('offers PayPal only on the Donate page', function () {
    $this->get(route('donate'))
        ->assertOk()
        ->assertSee('id="pay-paypal"', false)
        ->assertDontSee('id="pay-gcash"', false)
        ->assertDontSee('id="pay-bank"', false)
        ->assertDontSee('Online Banking');
});

it('refuses a GCash or bank donation', function (string $method) {
    Http::fake();

    $this->postJson(route('donate.checkout'), payMongoOffDonationPayload([
        'currency' => 'PHP',
        'amount' => 500,
        'payment_method' => $method,
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('payment_method');

    expect(Donation::count())->toBe(0);
    Http::assertNothingSent();
})->with(['gcash', 'bank']);

it('still takes a PayPal donation', function () {
    Http::fake();

    $this->mock(PayPalDonationService::class, function ($mock) {
        $mock->shouldReceive('createOrder')->once()->andReturn([
            'approval_url' => 'https://www.sandbox.paypal.com/checkoutnow?token=ORDER-D1',
            'order_id' => 'ORDER-D1',
        ]);
    });

    $this->postJson(route('donate.checkout'), payMongoOffDonationPayload())
        ->assertOk()
        ->assertJson(['redirect_url' => 'https://www.sandbox.paypal.com/checkoutnow?token=ORDER-D1']);

    Http::assertNothingSent();
});

it('does not retry a PayMongo payment', function () {
    Http::fake();

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
        'amount_usd' => 10,
        'amount_php' => 500,
        'platform_fee_usd' => 0,
        'frequency' => 'once',
        'payment_method' => 'gcash',
        'status' => 'cancelled',
        'paymongo_session_id' => 'cs_old_1',
    ]);

    $this->get(route('donate.retry', $donation))->assertRedirect(route('donate'));

    Http::assertNothingSent();
});

it('sends a chapter inquiry that pays now to PayPal', function () {
    Http::fake();

    $this->mock(PayPalDonationService::class, function ($mock) {
        $mock->shouldReceive('createTrainingOrder')->once()->andReturn([
            'approval_url' => 'https://www.sandbox.paypal.com/checkoutnow?token=ORDER-1',
            'order_id' => 'ORDER-1',
        ]);
    });

    $this->followingRedirects()
        ->post(route('replicate.inquiry.store'), payMongoOffInquiryPayload())
        ->assertOk()
        ->assertSee('https://www.sandbox.paypal.com/checkoutnow?token=ORDER-1', false);

    expect(ReplicationRequest::sole())
        ->paypal_order_id->toBe('ORDER-1')
        ->payment_status->toBe('pending')
        ->paymongo_session_id->toBeNull();

    Http::assertNothingSent();
});

it('marks the chapter fee paid when the organization comes back from PayPal', function () {
    $inquiry = makePayMongoOffInquiry(['payment_status' => 'pending', 'paypal_order_id' => 'ORDER-1']);

    $this->mock(PayPalDonationService::class, function ($mock) {
        $mock->shouldReceive('captureOrder')->once()->with('ORDER-1')->andReturn(payMongoOffCaptureResponse());
        $mock->shouldReceive('isCaptureSuccessful')->once()->andReturn(true);
    });

    $this->get(route('replicate.inquiry.show', [
        'replicationRequest' => $inquiry,
        'payment' => 'success',
        'token' => 'ORDER-1',
    ]))->assertOk()->assertSee('Payment received');

    expect($inquiry->fresh())
        ->payment_status->toBe('paid')
        ->paypal_capture_id->toBe('CAPTURE-1')
        ->paid_at->not->toBeNull();
});

it('leaves the chapter fee unpaid when the PayPal capture fails', function () {
    $inquiry = makePayMongoOffInquiry(['payment_status' => 'pending', 'paypal_order_id' => 'ORDER-1']);

    $this->mock(PayPalDonationService::class, function ($mock) {
        $mock->shouldReceive('captureOrder')->once()->andThrow(new RuntimeException('PayPal is down'));
    });

    $this->get(route('replicate.inquiry.show', [
        'replicationRequest' => $inquiry,
        'payment' => 'success',
        'token' => 'ORDER-1',
    ]))->assertOk();

    expect($inquiry->fresh())
        ->payment_status->toBe('pending')
        ->paypal_capture_id->toBeNull();
});

it('does not capture when the token is for another order', function () {
    $inquiry = makePayMongoOffInquiry(['payment_status' => 'pending', 'paypal_order_id' => 'ORDER-1']);

    $this->mock(PayPalDonationService::class, function ($mock) {
        $mock->shouldNotReceive('captureOrder');
    });

    $this->get(route('replicate.inquiry.show', [
        'replicationRequest' => $inquiry,
        'payment' => 'success',
        'token' => 'ORDER-OTHER',
    ]))->assertOk();

    expect($inquiry->fresh()->payment_status)->toBe('pending');
});

it('marks a chapter fee paid from the PayPal webhook', function () {
    $inquiry = makePayMongoOffInquiry(['payment_status' => 'pending', 'paypal_order_id' => 'ORDER-1']);

    $this->mock(PayPalDonationService::class, function ($mock) {
        $mock->shouldReceive('verifyWebhookLocally')->andReturn(true);
        $mock->shouldReceive('captureOrder')->once()->with('ORDER-1')->andReturn(payMongoOffCaptureResponse('CAPTURE-2'));
        $mock->shouldReceive('isCaptureSuccessful')->once()->andReturn(true);
    });

    $this->postJson('/webhooks/paypal', [
        'event_type' => 'CHECKOUT.ORDER.APPROVED',
        'resource' => [
            'id' => 'ORDER-1',
            'purchase_units' => [['reference_id' => $inquiry->uuid]],
        ],
    ])->assertOk();

    expect($inquiry->fresh())
        ->payment_status->toBe('paid')
        ->paypal_capture_id->toBe('CAPTURE-2');
});

it('says PayPal, not GCash, on the replicate page', function () {
    $this->get(route('replicate'))
        ->assertOk()
        ->assertSee('PayPal')
        ->assertDontSee('GCash');
});

it('uses PayMongo and shows GCash again once PayMongo is switched back on', function () {
    config(['payments.paymongo' => true, 'services.paymongo.secret_key' => 'sk_test_123']);
    Http::fake([
        'api.paymongo.com/v1/checkout_sessions' => Http::response([
            'data' => [
                'id' => 'cs_back_1',
                'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/cs_back_1'],
            ],
        ]),
    ]);

    $this->mock(PayPalDonationService::class, function ($mock) {
        $mock->shouldNotReceive('createTrainingOrder');
    });

    $this->get(route('donate'))
        ->assertOk()
        ->assertSee('id="pay-gcash"', false)
        ->assertSee('id="pay-bank"', false);

    $this->post(route('replicate.inquiry.store'), payMongoOffInquiryPayload())
        ->assertSessionHas('checkout_url', 'https://checkout.paymongo.com/cs_back_1');

    expect(ReplicationRequest::sole())
        ->paymongo_session_id->toBe('cs_back_1')
        ->paypal_order_id->toBeNull();
});
