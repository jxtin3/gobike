<?php

use App\Models\Barangay;
use App\Models\DeviceToken;
use App\Models\Location;
use App\Models\SosRequest;
use App\Models\SosResponder;
use App\Models\User;
use App\Services\Push\FcmSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/** Records the pushes instead of calling Firebase. */
class RespondersFakeFcm implements FcmSender
{
    /** @var list<array{token: string, title: string, body: string, data: array, channel: string}> */
    public array $sent = [];

    public function enabled(): bool
    {
        return true;
    }

    public function send(string $token, string $title, string $body, array $data, string $channel): bool
    {
        $this->sent[] = compact('token', 'title', 'body', 'data', 'channel');

        return true;
    }

    /** @return list<array{token: string, title: string, body: string, data: array, channel: string}> */
    public function to(string $token, ?string $type = null): array
    {
        return collect($this->sent)
            ->where('token', $token)
            ->when($type, fn ($c) => $c->filter(fn ($m) => ($m['data']['type'] ?? null) === $type))
            ->values()
            ->all();
    }
}

beforeEach(function () {
    $this->withoutDefer();
    $this->push = new RespondersFakeFcm;
    $this->app->instance(FcmSender::class, $this->push);

    Barangay::firstOrCreate(['name' => 'Poblacion'], ['municipality' => 'Bugallon']);
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->resident = User::factory()->create(['name' => 'Ana Reyes', 'role' => 'User', 'status' => 'Active', 'barangay' => 'Poblacion']);
    $this->maria = User::factory()->create(['name' => 'Maria Santos', 'role' => 'GoBiker', 'status' => 'Active', 'barangay' => 'Poblacion']);
    $this->jose = User::factory()->create(['name' => 'Jose Lim', 'role' => 'GoBiker', 'status' => 'Active', 'barangay' => 'Poblacion']);
    $this->rico = User::factory()->create(['name' => 'Rico Dela', 'role' => 'GoBiker', 'status' => 'Active', 'barangay' => 'Poblacion']);

    foreach ([$this->resident, $this->maria, $this->jose, $this->rico] as $user) {
        DeviceToken::create(['user_id' => $user->id, 'token' => "phone-{$user->id}"]);
    }
    foreach ([$this->maria, $this->jose, $this->rico] as $gobiker) {
        Location::create([
            'user_id' => $gobiker->id, 'name' => $gobiker->name, 'latitude' => 15.95, 'longitude' => 120.18,
            'status' => 'active', 'last_seen_at' => now(), 'active_start_time' => now(), 'active_end_time' => null,
        ]);
    }
    $this->sos = SosRequest::create([
        'user_id' => $this->resident->id, 'municipality' => 'Bugallon', 'barangay' => 'Poblacion',
        'address' => '12 Rizal St.', 'landmark' => 'Chapel', 'latitude' => 15.95, 'longitude' => 120.18, 'status' => 'sent',
    ]);
});

function phoneOf(User $user): string
{
    return "phone-{$user->id}";
}

function sendResponder(SosRequest $sos, User $gobiker): TestResponse
{
    return test()->actingAs(test()->admin, 'web')
        ->patchJson(route('admin.operations.sos.status', $sos), ['status' => 'responding', 'gobiker_id' => $gobiker->id]);
}

function declineAs(User $gobiker, SosRequest $sos, string $reason = 'Flat tire'): TestResponse
{
    Sanctum::actingAs($gobiker);

    return test()->postJson(route('api.gobiker.sos.decline', $sos), ['reason' => $reason]);
}

it('adds a second GoBiker instead of replacing the first', function () {
    sendResponder($this->sos, $this->maria)->assertOk();
    sendResponder($this->sos, $this->jose)->assertOk()->assertJsonPath('assigned_gobiker', 'Jose Lim');

    expect($this->sos->fresh())
        ->status->toBe('responding')
        ->assigned_gobiker_id->toBe($this->jose->id)
        ->and($this->sos->activeResponders()->pluck('gobiker_id')->sort()->values()->all())
        ->toBe(collect([$this->maria->id, $this->jose->id])->sort()->values()->all());

    Sanctum::actingAs($this->maria);
    $this->getJson(route('api.gobiker.sos.index'))->assertOk()
        ->assertJsonPath('sos.0.id', $this->sos->id)
        ->assertJsonPath('sos.0.other_responders', ['Jose Lim']);
    Sanctum::actingAs($this->jose);
    $this->getJson(route('api.gobiker.sos.index'))->assertOk()
        ->assertJsonPath('sos.0.other_responders', ['Maria Santos']);

    // Only the GoBiker just sent is alerted; the one already on the way is not alerted twice.
    expect($this->push->to(phoneOf($this->maria), 'sos_assigned'))->toHaveCount(1)
        ->and($this->push->to(phoneOf($this->jose), 'sos_assigned'))->toHaveCount(1);
});

it('tells the admin the GoBiker is now also responding', function () {
    sendResponder($this->sos, $this->maria)->assertOk();

    $this->actingAs($this->admin, 'web')
        ->from(route('admin.operations.sos.show', $this->sos))
        ->patch(route('admin.operations.sos.status', $this->sos), ['status' => 'responding', 'gobiker_id' => $this->jose->id])
        ->assertSessionHas('success', 'Jose Lim is now also responding.');
});

it('alerts a GoBiker again when they are sent after declining', function () {
    sendResponder($this->sos, $this->maria)->assertOk();
    declineAs($this->maria, $this->sos)->assertOk();
    expect($this->sos->fresh()->status)->toBe('declined');

    $this->travel(1)->minutes();
    Location::where('user_id', $this->maria->id)->update(['last_seen_at' => now()]); // still online
    sendResponder($this->sos, $this->maria)->assertOk();

    $row = SosResponder::where('sos_request_id', $this->sos->id)->where('gobiker_id', $this->maria->id)->sole();
    expect($row->status)->toBe('responding')
        ->and($row->decline_reason)->toBeNull()
        ->and($this->sos->fresh()->status)->toBe('responding');

    $alerts = $this->push->to(phoneOf($this->maria), 'sos_assigned');
    expect($alerts)->toHaveCount(2)
        ->and($alerts[0]['data']['assigned_at'])->not->toBe($alerts[1]['data']['assigned_at']);
});

it('refuses to send a GoBiker who is already responding', function () {
    sendResponder($this->sos, $this->maria)->assertOk();

    sendResponder($this->sos, $this->maria)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['gobiker_id' => 'Maria Santos is already responding to this request.']);
    expect($this->push->to(phoneOf($this->maria), 'sos_assigned'))->toHaveCount(1);
});

it('keeps the SOS responding when one of two declines', function () {
    sendResponder($this->sos, $this->maria)->assertOk();
    sendResponder($this->sos, $this->jose)->assertOk();

    declineAs($this->jose, $this->sos)->assertOk();

    expect($this->sos->fresh())
        ->status->toBe('responding')
        ->assigned_gobiker_id->toBe($this->maria->id);
    $this->actingAs($this->admin, 'web')->getJson(route('admin.emergencies.index'))
        ->assertOk()->assertJsonCount(0, 'emergencies');
    // The resident still has someone on the way: no "finding you help".
    expect(collect($this->push->to(phoneOf($this->resident)))->pluck('title'))->not->toContain('Finding you help');
    Sanctum::actingAs($this->jose);
    $this->getJson(route('api.gobiker.sos.index'))->assertOk()->assertJsonCount(0, 'sos');

    declineAs($this->maria, $this->sos)->assertOk();

    expect($this->sos->fresh()->status)->toBe('declined');
    $this->actingAs($this->admin, 'web')->getJson(route('admin.emergencies.index'))
        ->assertOk()->assertJsonCount(1, 'emergencies');
});

it('closes the SOS for everyone when one resolves', function () {
    sendResponder($this->sos, $this->maria)->assertOk();
    sendResponder($this->sos, $this->jose)->assertOk();

    Sanctum::actingAs($this->jose);
    $this->postJson(route('api.gobiker.sos.resolve', $this->sos))->assertOk();

    expect($this->sos->fresh())
        ->status->toBe('resolved')
        ->resolved_by_gobiker_id->toBe($this->jose->id);

    $standDown = collect($this->push->to(phoneOf($this->maria), 'sos_cancelled'));
    expect($standDown)->toHaveCount(1)
        ->and($standDown[0]['title'])->toBe('SOS resolved')
        ->and($standDown[0]['body'])->toBe('Jose Lim resolved the SOS. You can stand down.')
        ->and($this->push->to(phoneOf($this->jose), 'sos_cancelled'))->toHaveCount(0);

    Sanctum::actingAs($this->maria);
    $this->postJson(route('api.gobiker.sos.resolve', $this->sos))->assertUnprocessable();
    $this->postJson(route('api.gobiker.sos.decline', $this->sos), ['reason' => 'Too late'])->assertUnprocessable();

    // Shown as resolved for two minutes, then gone.
    $this->getJson(route('api.gobiker.map'))->assertOk()
        ->assertJsonCount(1, 'sos')->assertJsonPath('sos.0.status', 'resolved');
    $this->travel(3)->minutes();
    $this->getJson(route('api.gobiker.map'))->assertOk()->assertJsonCount(0, 'sos');
});

it('tells the resident about every GoBiker on the way', function () {
    sendResponder($this->sos, $this->maria)->assertOk();
    sendResponder($this->sos, $this->jose)->assertOk();

    $bodies = collect($this->push->to(phoneOf($this->resident), 'sos'))->pluck('body');
    expect($bodies->first())->toBe('Maria Santos is on the way to help you.')
        ->and($bodies->last())->toBe('Jose Lim and Maria Santos are on the way to help you.');

    Sanctum::actingAs($this->resident);
    $this->getJson(route('api.resident.sos.index'))->assertOk()
        ->assertJsonCount(2, 'sos.0.responders')
        ->assertJsonPath('sos.0.responder.name', 'Jose Lim');
    $this->getJson(route('api.resident.sos.tracking', $this->sos))->assertOk()
        ->assertJsonPath('tracking', true)
        ->assertJsonCount(2, 'responders')
        ->assertJsonPath('responder.name', 'Jose Lim')
        ->assertJsonPath('responders.1.name', 'Maria Santos');

    sendResponder($this->sos, $this->rico)->assertOk();
    expect(collect($this->push->to(phoneOf($this->resident), 'sos'))->last()['body'])
        ->toBe('Rico Dela and 2 other GoBikers are on the way to help you.');
});

it('leaves GoBikers already on the SOS out of the picker', function () {
    sendResponder($this->sos, $this->maria)->assertOk();

    $names = collect($this->actingAs($this->admin, 'web')
        ->getJson(route('admin.operations.sos.gobikers', $this->sos))->assertOk()->json('gobikers'))->pluck('name');

    expect($names)->not->toContain('Maria Santos')->toContain('Jose Lim');
});

it('lists every responder on the admin map pin, declined ones with their reason', function () {
    sendResponder($this->sos, $this->maria)->assertOk();
    sendResponder($this->sos, $this->jose)->assertOk();
    declineAs($this->jose, $this->sos, 'Flat tire')->assertOk();

    $pin = collect($this->actingAs($this->admin, 'web')->getJson(route('admin.sos-pins.index'))->assertOk()->json())
        ->firstWhere('id', $this->sos->id);

    expect($pin['user_id'])->toBe($this->resident->id)
        ->and($pin['assigned_gobiker'])->toBe('Maria Santos')
        ->and(collect($pin['responders'])->firstWhere('name', 'Jose Lim'))
        ->toMatchArray(['status' => 'declined', 'decline_reason' => 'Flat tire'])
        ->and(collect($pin['responders'])->firstWhere('name', 'Maria Santos')['status'])->toBe('responding');
});

it('keeps cancelled SOS off the GoBiker map, and tells every responder', function () {
    sendResponder($this->sos, $this->maria)->assertOk();
    sendResponder($this->sos, $this->jose)->assertOk();

    Sanctum::actingAs($this->resident);
    $this->postJson(route('api.resident.sos.cancel', $this->sos))->assertOk();

    expect($this->push->to(phoneOf($this->maria), 'sos_cancelled'))->toHaveCount(1)
        ->and($this->push->to(phoneOf($this->jose), 'sos_cancelled'))->toHaveCount(1);
    Sanctum::actingAs($this->maria);
    $this->getJson(route('api.gobiker.map'))->assertOk()->assertJsonCount(0, 'sos');
});

it('keeps the SOS in the history of the GoBikers who stayed on it only', function () {
    sendResponder($this->sos, $this->maria)->assertOk();
    sendResponder($this->sos, $this->jose)->assertOk();
    declineAs($this->maria, $this->sos)->assertOk();
    Sanctum::actingAs($this->jose);
    $this->postJson(route('api.gobiker.sos.resolve', $this->sos))->assertOk();

    $this->getJson(route('api.gobiker.history'))->assertOk()->assertJsonCount(1, 'sos');
    Sanctum::actingAs($this->maria);
    $this->getJson(route('api.gobiker.history'))->assertOk()->assertJsonCount(0, 'sos');
});

it('gives every SOS assigned before this change its responder', function () {
    Schema::drop('sos_responders');
    $base = [
        'user_id' => $this->resident->id, 'municipality' => 'Bugallon', 'barangay' => 'Poblacion',
        'landmark' => 'Chapel', 'created_at' => now(), 'updated_at' => now(),
    ];
    $responding = DB::table('sos_requests')->insertGetId($base + ['status' => 'responding', 'assigned_gobiker_id' => $this->maria->id, 'responded_at' => now()]);
    $declined = DB::table('sos_requests')->insertGetId($base + [
        'status' => 'declined', 'assigned_gobiker_id' => $this->jose->id,
        'declined_by_gobiker_id' => $this->jose->id, 'declined_at' => now(), 'decline_reason' => 'Out of town',
    ]);

    (require database_path('migrations/2026_11_26_000001_create_sos_responders_table.php'))->up();

    expect(SosResponder::count())->toBe(2)
        ->and(SosResponder::where('sos_request_id', $responding)->sole())
        ->gobiker_id->toBe($this->maria->id)->status->toBe('responding')
        ->and(SosResponder::where('sos_request_id', $declined)->sole())
        ->gobiker_id->toBe($this->jose->id)->status->toBe('declined')->decline_reason->toBe('Out of town');
});
