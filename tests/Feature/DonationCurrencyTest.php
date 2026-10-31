<?php

use App\Models\Donation;
use App\Models\PaypalDonationPlan;
use App\Services\ExchangeRateService;
use App\Services\PayMongoDonationService;
use App\Services\PayPalDonationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

function currencyCheckoutPayload(array $overrides = []): array
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

function makeCurrencyDonation(array $overrides = []): Donation
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
        'currency' => 'EUR',
        'amount' => 50,
        'platform_fee' => 1.75,
        'amount_usd' => 54.35,
        'platform_fee_usd' => 1.90,
        'frequency' => 'monthly',
        'payment_method' => 'paypal',
        'status' => 'pending',
    ], $overrides));
}

function fakePaypalApi(): void
{
    config([
        'paypal.mode' => 'sandbox',
        'paypal.sandbox.client_id' => 'id',
        'paypal.sandbox.client_secret' => 'secret',
        'donation.paypal.product_id' => '',
    ]);

    Http::fake([
        'api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'token']),
        'api-m.sandbox.paypal.com/v1/catalogs/products' => Http::response(['id' => 'PROD-1'], 201),
        'api-m.sandbox.paypal.com/v1/billing/plans' => Http::sequence()
            ->push(['id' => 'P-FIRST'], 201)
            ->push(['id' => 'P-SECOND'], 201),
        'api-m.sandbox.paypal.com/v1/billing/subscriptions' => Http::response([
            'id' => 'I-SUB-1',
            'links' => [['rel' => 'approve', 'href' => 'https://www.sandbox.paypal.com/approve']],
        ], 201),
    ]);
}

it('stores the donation in the donor currency with USD and PHP equivalents', function () {
    // Fallback rates (live rates are off in tests): EUR 0.92 and PHP 58 per USD.
    config(['donation.currencies.PHP.fallback_rate' => 58.0]);
    $this->mock(PayMongoDonationService::class)
        ->shouldReceive('createCheckoutSession')
        ->andReturn(['session_id' => 'cs_1', 'checkout_url' => 'https://example.test/pay']);

    $this->postJson(route('donate.checkout'), currencyCheckoutPayload([
        'currency' => 'EUR',
        'amount' => 46,
        'platform_fee' => 1.75,
        'payment_method' => 'gcash',
    ]))->assertOk();

    $donation = Donation::latest('id')->first();

    expect($donation->currency)->toBe('EUR')
        ->and((float) $donation->amount)->toBe(46.0)
        ->and((float) $donation->platform_fee)->toBe(1.75)
        ->and((float) $donation->amount_usd)->toBe(50.0)
        ->and((float) $donation->amount_php)->toBe(round(47.75 / 0.92 * 58, 2))
        ->and($donation->formatted_total)->toBe('€47.75');
});

it('charges PHP donations through GCash without converting them', function () {
    $this->mock(PayMongoDonationService::class)
        ->shouldReceive('createCheckoutSession')
        ->andReturn(['session_id' => 'cs_1', 'checkout_url' => 'https://example.test/pay']);

    $this->postJson(route('donate.checkout'), currencyCheckoutPayload([
        'currency' => 'PHP',
        'amount' => 1000,
        'platform_fee' => 100,
        'payment_method' => 'gcash',
    ]))->assertOk();

    expect((float) Donation::latest('id')->first()->amount_php)->toBe(1100.0);
});

it('rejects a currency we do not offer', function () {
    $this->postJson(route('donate.checkout'), currencyCheckoutPayload(['currency' => 'XYZ']))
        ->assertJsonValidationErrors('currency');
});

it('only accepts the platform fee shown for the currency', function () {
    $this->postJson(route('donate.checkout'), currencyCheckoutPayload(['currency' => 'EUR', 'platform_fee' => 1.88]))
        ->assertJsonValidationErrors('platform_fee');
});

it('applies each currency its own round limits', function (string $currency, float $amount, string $message) {
    $this->postJson(route('donate.checkout'), currencyCheckoutPayload(['currency' => $currency, 'amount' => $amount]))
        ->assertJsonValidationErrors(['amount' => $message]);
})->with([
    'too little in pesos' => ['PHP', 99, 'between ₱100 and ₱500,000'],
    'too much in yen' => ['JPY', 1500001, 'between ¥300 and ¥1,500,000'],
    'too little in dollars' => ['USD', 1, 'between $2 and $10,000'],
]);

it('rejects cents for currencies that have none', function () {
    $this->postJson(route('donate.checkout'), currencyCheckoutPayload(['currency' => 'JPY', 'amount' => 4000.5]))
        ->assertJsonValidationErrors('amount');
});

it('creates PayPal orders in the donor currency, without decimals for yen', function () {
    $client = Mockery::mock(PayPalClient::class);
    $client->shouldReceive('createOrder')->once()
        ->withArgs(fn (array $order) => $order['purchase_units'][0]['amount'] === ['currency_code' => 'JPY', 'value' => '4290'])
        ->andReturn(['status' => 'CREATED', 'id' => 'ORDER-1', 'links' => [['rel' => 'approve', 'href' => 'https://paypal.test/approve']]]);

    config(['paypal.mode' => 'sandbox', 'paypal.sandbox.client_id' => 'id']);
    $service = new PayPalDonationService;
    (new ReflectionProperty($service, 'client'))->setValue($service, $client);

    $result = $service->createOrder(makeCurrencyDonation([
        'currency' => 'JPY', 'amount' => 4000, 'platform_fee' => 290, 'frequency' => 'once',
    ]));

    expect($result['order_id'])->toBe('ORDER-1');
});

it('creates a PayPal plan once per currency, then subscribes in that currency', function () {
    fakePaypalApi();
    $paypal = app(PayPalDonationService::class);

    $paypal->createSubscription(makeCurrencyDonation());
    $paypal->createSubscription(makeCurrencyDonation());
    $paypal->createSubscription(makeCurrencyDonation(['currency' => 'GBP', 'amount' => 40, 'platform_fee' => 0]));

    expect(PaypalDonationPlan::pluck('plan_id', 'currency')->all())->toBe(['EUR' => 'P-FIRST', 'GBP' => 'P-SECOND']);

    $plans = Http::recorded(fn ($request) => str_ends_with($request->url(), '/v1/billing/plans'));
    $products = Http::recorded(fn ($request) => str_ends_with($request->url(), '/v1/catalogs/products'));
    expect($plans)->toHaveCount(2)
        ->and($products)->toHaveCount(1)
        ->and($plans->first()[0]['billing_cycles'][0]['pricing_scheme']['fixed_price']['currency_code'])->toBe('EUR');

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/v1/billing/subscriptions')
        && $request['plan_id'] === 'P-FIRST'
        && $request['plan']['billing_cycles'][0]['pricing_scheme']['fixed_price'] === ['value' => '51.75', 'currency_code' => 'EUR']);
});

it('keeps sandbox and live plans apart', function () {
    PaypalDonationPlan::create(['mode' => 'live', 'currency' => 'EUR', 'product_id' => 'PROD-LIVE', 'plan_id' => 'P-LIVE']);
    fakePaypalApi(); // sandbox

    expect(app(PayPalDonationService::class)->planIdFor('EUR'))->toBe('P-FIRST');
});

it('uses fetched rates and falls back to the last good ones when the rates API fails', function () {
    config(['donation.live_rates' => true]);
    Http::fake(['api.frankfurter.app/*' => Http::sequence()
        ->push(['rates' => ['PHP' => 57.5, 'EUR' => 0.9]])
        ->push('down', 500)]);

    expect(app(ExchangeRateService::class)->rate('PHP'))->toBe(57.5);

    Cache::forget('donation.rates'); // the day is over, and now the API is down

    $rates = app(ExchangeRateService::class);
    expect($rates->rate('PHP'))->toBe(57.5)
        ->and($rates->rate('JPY'))->toBe(150.0); // missing from the API: config fallback
});

it('uses the config fallback rates before any rates were fetched', function () {
    config(['donation.live_rates' => true, 'donation.currencies.PHP.fallback_rate' => 60.0]);
    Http::fake(['api.frankfurter.app/*' => Http::response('down', 500)]);

    expect(app(ExchangeRateService::class)->rate('PHP'))->toBe(60.0);
});

it('shows the currency dropdown with every donation currency', function () {
    $this->get(route('donate'))
        ->assertOk()
        ->assertSee('<label for="donate-currency">This donation is in</label>', false)
        ->assertSee('EUR — Euro')
        ->assertSee('JPY — Japanese Yen');
});

it('thanks the donor in their own currency', function () {
    $donation = makeCurrencyDonation([
        'frequency' => 'once', 'payment_method' => 'gcash', 'status' => 'paid', 'amount_php' => 3010.50,
    ]);

    $this->get(route('donate.success', $donation))
        ->assertOk()
        ->assertSee('€51.75')
        ->assertSee('₱3,010.50');
});

it('shows admins the original currency next to the USD equivalent', function () {
    $admin = App\Models\User::factory()->create(['is_admin' => true]);
    makeCurrencyDonation(['frequency' => 'once', 'status' => 'paid', 'amount' => 46, 'amount_usd' => 50]);

    $this->actingAs($admin)
        ->get(route('admin.operations.donations.index'))
        ->assertOk()
        ->assertSee('€46.00')
        ->assertSee('≈ $50.00');
});
