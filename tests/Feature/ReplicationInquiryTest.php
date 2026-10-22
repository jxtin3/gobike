<?php

use App\Models\ReplicationRequest;
use App\Models\User;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;

function replicationPayload(array $overrides = []): array
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
        'payment' => 'later',
        'agree' => '1',
    ], $overrides);
}

function makeReplicationInquiry(array $overrides = []): ReplicationRequest
{
    return ReplicationRequest::create(array_merge(
        collect(replicationPayload())->except(['payment', 'agree'])->all(),
        ['amount_php' => 32000],
        $overrides,
    ));
}

function fakePaymongoCheckout(string $sessionId = 'cs_test_1'): void
{
    config(['services.paymongo.secret_key' => 'sk_test_123']);

    Http::fake([
        'api.paymongo.com/v1/checkout_sessions' => Http::response([
            'data' => [
                'id' => $sessionId,
                'attributes' => ['checkout_url' => "https://checkout.paymongo.com/{$sessionId}"],
            ],
        ]),
    ]);
}

it('shows the inquiry form and training fee on the replicate page', function () {
    $this->get(route('replicate'))
        ->assertOk()
        ->assertSee('id="inquire"', false)
        ->assertSee('Bring Go Bike to Your Community')
        ->assertSee('Inquire now')
        ->assertSee('action="'.route('replicate.inquiry.store').'"', false)
        ->assertSee('₱30,000.00');
});

it('calculates the training fee from the number of trainees', function () {
    expect(ReplicationRequest::feeFor(1))->toBe(30000)
        ->and(ReplicationRequest::feeFor(15))->toBe(30000)
        ->and(ReplicationRequest::feeFor(16))->toBe(32000)
        ->and(ReplicationRequest::feeFor(50))->toBe(100000);
});

it('saves an inquiry without payment and calculates the fee on the server', function () {
    $this->post(route('replicate.inquiry.store'), replicationPayload(['amount_php' => 1]))
        ->assertRedirect(route('replicate').'#inquire')
        ->assertSessionHas('replication_success');

    $inquiry = ReplicationRequest::sole();

    expect((float) $inquiry->amount_php)->toBe(32000.0)
        ->and($inquiry->payment_status)->toBe('unpaid')
        ->and($inquiry->status)->toBe('pending');
});

it('validates the inquiry and returns to the form', function () {
    $this->post(route('replicate.inquiry.store'), replicationPayload(['trainees' => 51, 'agree' => null]))
        ->assertRedirect(route('replicate').'#inquire')
        ->assertSessionHasErrors(['trainees', 'agree']);

    expect(ReplicationRequest::count())->toBe(0);
});

it('starts a PayMongo checkout when the organization pays early', function () {
    fakePaymongoCheckout();

    $response = $this->post(route('replicate.inquiry.store'), replicationPayload(['payment' => 'online']));

    $inquiry = ReplicationRequest::sole();
    $response->assertRedirect(route('replicate.inquiry.show', $inquiry))
        ->assertSessionHas('checkout_url', 'https://checkout.paymongo.com/cs_test_1');

    expect($inquiry->payment_status)->toBe('pending')
        ->and($inquiry->paymongo_session_id)->toBe('cs_test_1');

    Http::assertSent(function (HttpRequest $request) use ($inquiry) {
        $attributes = $request['data']['attributes'];

        return $attributes['line_items'][0]['amount'] === 3200000
            && $attributes['payment_method_types'] === ['gcash', 'dob']
            && $attributes['metadata']['replication_uuid'] === $inquiry->uuid;
    });
});

it('hands off to PayMongo from the status page', function () {
    $inquiry = makeReplicationInquiry(['payment_status' => 'pending', 'paymongo_session_id' => 'cs_test_1']);

    $this->withSession(['checkout_url' => 'https://checkout.paymongo.com/cs_test_1'])
        ->get(route('replicate.inquiry.show', $inquiry))
        ->assertOk()
        ->assertSee('Taking you to secure payment')
        ->assertSee('https://checkout.paymongo.com/cs_test_1', false);
});

it('keeps the inquiry when the online payment cannot start', function () {
    config(['services.paymongo.secret_key' => 'sk_test_123']);
    Http::fake(['api.paymongo.com/*' => Http::response(['errors' => [['detail' => 'Down']]], 500)]);

    $this->post(route('replicate.inquiry.store'), replicationPayload(['payment' => 'online']))
        ->assertSessionHas('error');

    expect(ReplicationRequest::sole()->payment_status)->toBe('unpaid');
});

it('confirms the payment when the organization returns from PayMongo', function () {
    config(['services.paymongo.secret_key' => 'sk_test_123']);
    Http::fake([
        'api.paymongo.com/v1/checkout_sessions/cs_test_2' => Http::response([
            'data' => ['id' => 'cs_test_2', 'attributes' => [
                'payments' => [['id' => 'pay_test_1', 'attributes' => ['status' => 'paid']]],
            ]],
        ]),
    ]);
    $inquiry = makeReplicationInquiry(['payment_status' => 'pending', 'paymongo_session_id' => 'cs_test_2']);

    $this->get(route('replicate.inquiry.show', ['replicationRequest' => $inquiry, 'payment' => 'success']))
        ->assertOk()
        ->assertSee('Payment received');

    expect($inquiry->fresh())
        ->payment_status->toBe('paid')
        ->paymongo_payment_id->toBe('pay_test_1');
});

it('marks the training fee paid from the PayMongo webhook', function () {
    $secret = 'whsk_test_secret';
    config(['services.paymongo.webhook_secret' => $secret]);
    $inquiry = makeReplicationInquiry(['payment_status' => 'pending', 'paymongo_session_id' => 'cs_test_3']);

    $body = json_encode(['data' => ['attributes' => [
        'type' => 'checkout_session.payment.paid',
        'data' => ['attributes' => [
            'metadata' => ['replication_uuid' => $inquiry->uuid],
            'payments' => [['id' => 'pay_test_2']],
        ]],
    ]]]);
    $timestamp = (string) time();
    $signature = hash_hmac('sha256', $timestamp.'.'.$body, $secret);

    $this->call('POST', '/webhooks/paymongo', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_PAYMONGO_SIGNATURE' => "t={$timestamp},v1={$signature}",
    ], $body)->assertOk();

    expect($inquiry->fresh())
        ->payment_status->toBe('paid')
        ->paymongo_payment_id->toBe('pay_test_2');
});

it('lets organizations pay again, but never twice', function () {
    fakePaymongoCheckout('cs_test_4');
    $unpaid = makeReplicationInquiry();

    $this->get(route('replicate.inquiry.pay', $unpaid))
        ->assertRedirect('https://checkout.paymongo.com/cs_test_4');

    Http::fake();
    $paid = makeReplicationInquiry(['payment_status' => 'paid', 'paid_at' => now()]);

    $this->get(route('replicate.inquiry.pay', $paid))
        ->assertRedirect(route('replicate.inquiry.show', $paid));

    Http::assertNothingSent();
});

it('lets admins review inquiries and record check payments', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $inquiry = makeReplicationInquiry();

    $this->actingAs($admin)
        ->get(route('admin.operations.chapter-inquiries.index'))
        ->assertOk()
        ->assertSee('SK of Barangay Poblacion');

    $this->actingAs($admin)
        ->get(route('admin.operations.chapter-inquiries.show', $inquiry))
        ->assertOk()
        ->assertSee($inquiry->reference);

    $this->actingAs($admin)
        ->patch(route('admin.operations.chapter-inquiries.update', $inquiry), ['payment_status' => 'paid'])
        ->assertRedirect();

    expect($inquiry->fresh())
        ->payment_status->toBe('paid')
        ->paid_at->not->toBeNull();
});

it('publishes the training fee and refund rules in the terms', function () {
    $this->get(route('terms'))
        ->assertOk()
        ->assertSee('Paying early online')
        ->assertSee('<strong>7 days</strong>', false);
});

it('clears the paid date when a payment is reset, but keeps it for refunds', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $inquiry = makeReplicationInquiry(['payment_status' => 'paid', 'paid_at' => now()->subDay()]);

    $this->actingAs($admin)
        ->patch(route('admin.operations.chapter-inquiries.update', $inquiry), ['payment_status' => 'refunded'])
        ->assertRedirect();
    expect($inquiry->fresh()->paid_at)->not->toBeNull();

    $this->actingAs($admin)
        ->patch(route('admin.operations.chapter-inquiries.update', $inquiry), ['payment_status' => 'unpaid'])
        ->assertRedirect();
    expect($inquiry->fresh()->paid_at)->toBeNull();
});

it('rejects an empty chapter inquiry update', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $inquiry = makeReplicationInquiry();

    $this->actingAs($admin)
        ->patch(route('admin.operations.chapter-inquiries.update', $inquiry), [])
        ->assertSessionHasErrors(['status', 'payment_status']);
});

it('asks before marking a chapter training paid or refunded', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $inquiry = makeReplicationInquiry();

    $this->actingAs($admin)
        ->get(route('admin.operations.chapter-inquiries.show', $inquiry))
        ->assertSee('data-confirm-title="Mark this training as paid?"', false)
        ->assertSee('data-confirm-title="Mark this payment as refunded?"', false)
        ->assertDontSee('Paid on');
});

it('lets admins delete unpaid chapter inquiries but keeps paid ones', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $unpaid = makeReplicationInquiry();
    $paid = makeReplicationInquiry(['email' => 'paid@example.com', 'payment_status' => 'paid', 'paid_at' => now()]);

    $this->actingAs($admin)
        ->delete(route('admin.operations.chapter-inquiries.destroy', $unpaid))
        ->assertRedirect(route('admin.operations.chapter-inquiries.index'));
    $this->assertModelMissing($unpaid);

    $this->actingAs($admin)
        ->delete(route('admin.operations.chapter-inquiries.destroy', $paid))
        ->assertSessionHas('error');
    $this->assertModelExists($paid);
});
