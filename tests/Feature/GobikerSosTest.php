<?php

use App\Models\Barangay;
use App\Models\DeviceToken;
use App\Models\Location;
use App\Models\SosRequest;
use App\Models\User;
use App\Services\Push\FcmSender;
use App\Services\Push\PushNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    Barangay::firstOrCreate(['name' => 'Poblacion'], ['municipality' => 'Bugallon']);
    $this->gobiker = User::factory()->create([
        'name' => 'Maria Santos', 'role' => 'GoBiker', 'status' => 'Active', 'barangay' => 'Poblacion',
    ]);
    $this->helper = User::factory()->create([
        'name' => 'Jose Lim', 'role' => 'GoBiker', 'status' => 'Active', 'barangay' => 'Poblacion',
    ]);
    $this->admin = User::factory()->create(['is_admin' => true]);
});

function raiseGobikerEmergency(User $gobiker, array $fix = []): TestResponse
{
    Sanctum::actingAs($gobiker);

    return test()->postJson(route('api.gobiker.emergency'), $fix + ['latitude' => 15.95, 'longitude' => 120.18]);
}

function gobikerLocation(User $gobiker): Location
{
    return Location::where('user_id', $gobiker->id)->sole();
}

// ---------------------------------------------------------------- Task 1: the GoBiker's own SOS

it('opens an SOS the GoBiker owns when they send an emergency', function () {
    raiseGobikerEmergency($this->gobiker)
        ->assertOk()
        ->assertJson(['message' => 'Emergency alert sent to the RHU admin.']);

    $sos = SosRequest::sole();
    expect($sos->user_id)->toBe($this->gobiker->id)
        ->and($sos->status)->toBe('sent')
        ->and($sos->barangay)->toBe('Poblacion')
        ->and($sos->municipality)->toBe('Bugallon')
        ->and($sos->latitude)->toBe(15.95)
        ->and($sos->landmark)->toBe('')
        ->and($sos->fromGobiker())->toBeTrue()
        ->and(gobikerLocation($this->gobiker)->status)->toBe('emergency');
});

it('reuses the open SOS when the GoBiker presses again', function () {
    raiseGobikerEmergency($this->gobiker)->assertOk();
    $this->travel(5)->seconds();
    raiseGobikerEmergency($this->gobiker, ['latitude' => 15.96, 'longitude' => 120.19])->assertOk();

    expect(SosRequest::count())->toBe(1)
        ->and(SosRequest::sole()->latitude)->toBe(15.96);
});

it('moves the SOS with the GoBiker while it is open', function () {
    raiseGobikerEmergency($this->gobiker)->assertOk();
    $this->travel(5)->seconds();

    $this->postJson(route('api.gobiker.location'), ['latitude' => 15.97, 'longitude' => 120.2])->assertOk();

    expect(SosRequest::sole()->latitude)->toBe(15.97)
        ->and(gobikerLocation($this->gobiker)->status)->toBe('emergency');
});

it('keeps the old Location-only emergency when the barangay is unknown', function () {
    $this->gobiker->update(['barangay' => null]);

    raiseGobikerEmergency($this->gobiker)->assertOk();

    expect(SosRequest::count())->toBe(0)
        ->and(gobikerLocation($this->gobiker)->status)->toBe('emergency');
    $this->getJson(route('api.gobiker.emergency.status'))->assertExactJson(['status' => 'emergency', 'sos' => null]);
});

it('returns the emergency with who is coming', function () {
    raiseGobikerEmergency($this->gobiker)->assertOk();
    $sos = SosRequest::sole();
    $sos->update(['status' => 'responding', 'assigned_gobiker_id' => $this->helper->id, 'responded_at' => now()]);
    gobikerLocation($this->gobiker)->update(['status' => 'responding']);

    $this->getJson(route('api.gobiker.emergency.status'))
        ->assertOk()
        ->assertJsonPath('status', 'responding')
        ->assertJsonPath('sos.id', $sos->id)
        ->assertJsonPath('sos.status', 'responding')
        ->assertJsonPath('sos.responder.name', 'Jose Lim');
});

it('does not ring again when pressed while a helper is on the way', function () {
    raiseGobikerEmergency($this->gobiker)->assertOk();
    $sos = SosRequest::sole();
    $sos->update(['status' => 'responding', 'assigned_gobiker_id' => $this->helper->id]);
    gobikerLocation($this->gobiker)->update(['status' => 'responding']);

    raiseGobikerEmergency($this->gobiker)->assertOk();

    expect($sos->fresh()->status)->toBe('responding')
        ->and(gobikerLocation($this->gobiker)->status)->toBe('responding');
});

it('lets a GoBiker cancel their emergency and tells the helper', function () {
    $push = $this->spy(PushNotifier::class);
    raiseGobikerEmergency($this->gobiker)->assertOk();
    $sos = SosRequest::sole();
    $sos->update(['status' => 'responding', 'assigned_gobiker_id' => $this->helper->id]);

    $this->postJson(route('api.gobiker.emergency.cancel'))
        ->assertOk()
        ->assertJsonPath('message', 'Emergency cancelled.')
        ->assertJsonPath('sos.cancelled', true);

    $location = gobikerLocation($this->gobiker);
    expect($sos->fresh()->status)->toBe('cancelled')
        ->and($location->status)->toBe('active')
        ->and($location->emergency_at)->toBeNull();
    $push->shouldHaveReceived('sosCancelled')->once()
        ->withArgs(fn (SosRequest $s) => $s->assignedGobiker?->id === $this->helper->id);

    $this->postJson(route('api.gobiker.emergency.cancel'))->assertStatus(422);
    $this->getJson(route('api.gobiker.emergency.status'))->assertJsonPath('status', null);
});

it('does not let a GoBiker track someone else\'s emergency', function () {
    raiseGobikerEmergency($this->gobiker)->assertOk();
    $sos = SosRequest::sole();

    Sanctum::actingAs($this->helper);
    $this->getJson(route('api.gobiker.emergency.tracking', $sos))->assertForbidden();

    Sanctum::actingAs($this->gobiker);
    $this->getJson(route('api.gobiker.emergency.tracking', $sos))
        ->assertOk()
        ->assertJsonPath('sos.id', $sos->id)
        ->assertJsonPath('tracking', false);
});

it('is only for signed-in GoBikers', function () {
    $this->postJson(route('api.gobiker.emergency.cancel'))->assertUnauthorized();

    Sanctum::actingAs(User::factory()->create(['role' => 'User']));
    $this->postJson(route('api.gobiker.emergency.cancel'))->assertForbidden();
});

// ------------------------------------------------- Task 2: the admin sends help, one alert, labels

function helperOnline(User $gobiker, float $latitude = 15.951, float $longitude = 120.181): void
{
    Location::updateOrCreate(['user_id' => $gobiker->id], [
        'name' => $gobiker->name, 'designated_barangay' => $gobiker->barangay,
        'latitude' => $latitude, 'longitude' => $longitude,
        'status' => 'active', 'last_seen_at' => now(),
    ]);
}

it('lets the admin send another GoBiker to a GoBiker\'s emergency', function () {
    raiseGobikerEmergency($this->gobiker)->assertOk();
    $sos = SosRequest::sole();
    helperOnline($this->helper);

    $this->actingAs($this->admin, 'web')
        ->patchJson(route('admin.operations.sos.status', $sos), ['status' => 'responding', 'gobiker_id' => $this->helper->id])
        ->assertOk()
        ->assertJsonPath('assigned_gobiker', 'Jose Lim');

    expect($sos->fresh()->status)->toBe('responding')
        ->and(gobikerLocation($this->gobiker)->status)->toBe('responding');

    Sanctum::actingAs($this->helper);
    $this->getJson(route('api.gobiker.sos.index'))
        ->assertOk()
        ->assertJsonPath('sos.0.id', $sos->id)
        ->assertJsonPath('sos.0.from_gobiker', true)
        ->assertJsonPath('sos.0.resident_name', 'Maria Santos');
});

it('never offers or accepts the GoBiker in trouble as their own helper', function () {
    raiseGobikerEmergency($this->gobiker)->assertOk();
    $sos = SosRequest::sole();
    gobikerLocation($this->gobiker)->update(['last_seen_at' => now()]);
    helperOnline($this->helper);

    $ids = collect($this->actingAs($this->admin, 'web')
        ->getJson(route('admin.operations.sos.gobikers', $sos))
        ->assertOk()
        ->json('gobikers'))->pluck('id');

    expect($ids)->toContain($this->helper->id)
        ->not->toContain($this->gobiker->id);

    $this->patchJson(route('admin.operations.sos.status', $sos), ['status' => 'responding', 'gobiker_id' => $this->gobiker->id])
        ->assertStatus(422)
        ->assertJsonValidationErrors('gobiker_id');
    expect($sos->fresh()->status)->toBe('sent');
});

it('lists a GoBiker\'s emergency once', function () {
    raiseGobikerEmergency($this->gobiker)->assertOk();
    $sos = SosRequest::sole();

    $rows = $this->actingAs($this->admin, 'web')->getJson(route('admin.emergencies.index'))->assertOk()->json('emergencies');

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['sos_id'])->toBe($sos->id)
        ->and($rows[0]['from'])->toBe('gobiker')
        ->and(Location::unansweredEmergencies()->count() + SosRequest::unanswered()->count())->toBe(1);

    $this->getJson(route('admin.sos-pins.index'))->assertOk()->assertJsonPath('0.from', 'gobiker');
});

it('moves the SOS too when the admin uses the buttons on the GoBiker\'s marker', function () {
    raiseGobikerEmergency($this->gobiker)->assertOk();
    $sos = SosRequest::sole();
    $location = gobikerLocation($this->gobiker);

    $this->actingAs($this->admin, 'web')
        ->patchJson(route('admin.locations.status', $location), ['status' => 'responding'])
        ->assertOk();
    expect($sos->fresh()->status)->toBe('responding')
        ->and($sos->fresh()->handled_by)->toBe($this->admin->id);

    $this->patchJson(route('admin.locations.status', $location), ['status' => 'active'])->assertOk();
    expect($sos->fresh()->status)->toBe('resolved')
        ->and($sos->fresh()->resolved_at)->not->toBeNull()
        ->and($location->fresh()->emergency_at)->toBeNull();
});

it('puts the GoBiker back to normal when the helper resolves it', function () {
    raiseGobikerEmergency($this->gobiker)->assertOk();
    $sos = SosRequest::sole();
    $sos->update(['status' => 'responding', 'assigned_gobiker_id' => $this->helper->id]);
    gobikerLocation($this->gobiker)->update(['status' => 'responding']);

    Sanctum::actingAs($this->helper);
    $this->postJson(route('api.gobiker.sos.resolve', $sos))->assertOk();

    expect(gobikerLocation($this->gobiker)->status)->toBe('active')
        ->and(gobikerLocation($this->gobiker)->emergency_at)->toBeNull();
});

it('keeps the GoBiker in emergency when the helper declines', function () {
    raiseGobikerEmergency($this->gobiker)->assertOk();
    $sos = SosRequest::sole();
    $sos->update(['status' => 'responding', 'assigned_gobiker_id' => $this->helper->id]);
    gobikerLocation($this->gobiker)->update(['status' => 'responding']);

    Sanctum::actingAs($this->helper);
    $this->postJson(route('api.gobiker.sos.decline', $sos), ['reason' => 'Flat tyre'])->assertOk();

    expect(gobikerLocation($this->gobiker)->status)->toBe('emergency');
    $rows = $this->actingAs($this->admin, 'web')->getJson(route('admin.emergencies.index'))->json('emergencies');
    expect($rows)->toHaveCount(1)
        ->and($rows[0]['declined'])->toBeTrue();
});

it('tells the GoBiker in trouble who is coming with an emergency push', function () {
    $this->withoutDefer();
    $push = new class
    {
        public array $sent = [];
    };
    raiseGobikerEmergency($this->gobiker)->assertOk();
    $sos = SosRequest::sole();
    $sos->update(['status' => 'responding', 'assigned_gobiker_id' => $this->helper->id]);

    $sender = Mockery::mock(FcmSender::class);
    $sender->shouldReceive('enabled')->andReturnTrue();
    $sender->shouldReceive('send')->andReturnUsing(function (...$args) use ($push) {
        $push->sent[] = $args;

        return true;
    });
    DeviceToken::create(['user_id' => $this->gobiker->id, 'token' => 'tok-maria']);

    (new PushNotifier($sender))->sosStatusChanged($sos->load('user', 'assignedGobiker'));

    expect($push->sent)->toHaveCount(1)
        ->and(json_encode($push->sent[0]))->toContain('"type":"emergency"')
        ->and(json_encode($push->sent[0]))->toContain('Jose Lim is on the way');
});
