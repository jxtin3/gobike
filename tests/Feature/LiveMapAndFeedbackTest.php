<?php

use App\Models\Barangay;
use App\Models\City;
use App\Models\Feedback;
use App\Models\Location;
use App\Models\Municipality;
use App\Models\Patient;
use App\Models\Province;
use App\Models\SosRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->resident = User::factory()->create(['name' => 'Ana Reyes', 'role' => 'User', 'status' => 'Active', 'barangay' => 'Poblacion']);
    $this->neighbor = User::factory()->create(['name' => 'Ben Cruz', 'role' => 'User', 'status' => 'Active', 'barangay' => 'Poblacion']);
    $this->gobiker = User::factory()->create(['name' => 'Maria Santos', 'role' => 'GoBiker', 'status' => 'Active', 'barangay' => 'Poblacion']);
    $this->colleague = User::factory()->create(['name' => 'Jose Lim', 'role' => 'GoBiker', 'status' => 'Active', 'barangay' => 'Poblacion']);
    $this->faraway = User::factory()->create(['name' => 'Rico Dela', 'role' => 'GoBiker', 'status' => 'Active', 'barangay' => 'Hacienda']);
});

function onlineGobiker(User $gobiker, float $latitude = 15.95, float $longitude = 120.18): void
{
    Location::create([
        'user_id' => $gobiker->id, 'name' => $gobiker->name, 'latitude' => $latitude, 'longitude' => $longitude,
        'status' => 'active', 'last_seen_at' => now(),
        'active_start_time' => now(),
        'active_end_time' => null,
    ]);
}

function liveSos(User $resident, array $attributes = []): SosRequest
{
    return SosRequest::create($attributes + [
        'user_id' => $resident->id, 'municipality' => 'Bugallon', 'barangay' => $resident->barangay,
        'address' => '12 Rizal St.', 'landmark' => 'Chapel', 'latitude' => 15.95, 'longitude' => 120.18, 'status' => 'sent',
    ]);
}

// ---------------------------------------------------------------- live map

/** The row a GoBiker leaves after stopping their ronda with the app still open. */
function onlineNotOnRonda(User $gobiker): void
{
    Location::create([
        'user_id' => $gobiker->id, 'name' => $gobiker->name, 'latitude' => 15.95, 'longitude' => 120.18,
        'status' => 'offline', 'last_seen_at' => now(),
        'active_start_time' => null,
        'active_end_time' => now()->subMinutes(5),
    ]);
}

/** A neighbouring town linked through the location tables, with its own "Poblacion". */
function aguilarPoblacion(): void
{
    $province = Province::create(['name' => 'Pangasinan', 'region' => 'Region I']);
    $city = City::create(['province_id' => $province->id, 'name' => 'Pangasinan']);
    $aguilar = Municipality::create(['city_id' => $city->id, 'name' => 'Aguilar']);
    // The text column still says Bugallon: the linked town must win.
    Barangay::create(['name' => 'Poblacion (Aguilar)', 'municipality' => 'Bugallon', 'municipality_id' => $aguilar->id]);
}

/** @return list<string> */
function mapNames(array $map): array
{
    return collect($map['gobikers'])->pluck('name')->sort()->values()->all();
}

it('shows a resident every online GoBiker of their town', function () {
    onlineGobiker($this->gobiker);
    onlineGobiker($this->faraway);

    Sanctum::actingAs($this->resident);
    $map = $this->getJson(route('api.resident.map'))->assertOk()->json();

    expect(mapNames($map))->toBe(['Maria Santos', 'Rico Dela'])
        ->and($map)->not->toHaveKey('residents')
        ->and($map['town'])->toBe('Bugallon')
        ->and($map['barangay'])->toBe('Poblacion');
});

it('never plots residents for a GoBiker, but shows every other online GoBiker of the town', function () {
    onlineGobiker($this->colleague);
    onlineGobiker($this->faraway);
    onlineGobiker($this->gobiker);

    Sanctum::actingAs($this->gobiker);
    $map = $this->getJson(route('api.gobiker.map'))->assertOk()->json();

    // A GoBiker picks their own barangay, so plotting its residents would let anyone
    // watch residents anywhere in the town. Nobody is plotted but fellow GoBikers.
    expect($map)->not->toHaveKey('residents')
        ->and(mapNames($map))->toBe(['Jose Lim', 'Rico Dela'])
        ->and(collect($map['gobikers'])->firstWhere('name', 'Rico Dela')['barangay'])->toBe('Hacienda')
        ->and($map['town'])->toBe('Bugallon');
});

it('shows a GoBiker whose app is open even when they are not on a ronda', function () {
    onlineNotOnRonda($this->colleague);

    Sanctum::actingAs($this->gobiker);
    $gobikers = collect($this->getJson(route('api.gobiker.map'))->assertOk()->json('gobikers'));

    expect($gobikers->pluck('name')->all())->toBe(['Jose Lim'])
        ->and($gobikers->first()['status'])->toBe('active');

    // An alert is still shown as one.
    Location::where('user_id', $this->colleague->id)->update(['status' => 'emergency']);
    expect($this->getJson(route('api.gobiker.map'))->json('gobikers.0.status'))->toBe('emergency');
});

it('matches towns through the old text column, as seeded Bugallon barangays have no link', function () {
    expect(Barangay::where('name', 'Hacienda')->value('municipality_id'))->toBeNull();
    onlineGobiker($this->faraway);

    Sanctum::actingAs($this->resident);

    expect(mapNames($this->getJson(route('api.resident.map'))->assertOk()->json()))->toBe(['Rico Dela']);
});

it('hides GoBikers of another town, the linked town winning over the text column', function () {
    aguilarPoblacion();
    $aguilarGobiker = User::factory()->create(['name' => 'Lito Aguilar', 'role' => 'GoBiker', 'status' => 'Active', 'barangay' => 'Poblacion (Aguilar)']);
    onlineGobiker($aguilarGobiker);
    onlineGobiker($this->gobiker);

    Sanctum::actingAs($this->resident);

    expect(mapNames($this->getJson(route('api.resident.map'))->assertOk()->json()))->toBe(['Maria Santos']);
});

it('drops GoBikers whose app went quiet', function () {
    onlineGobiker($this->colleague);
    Location::where('user_id', $this->colleague->id)->update(['last_seen_at' => now()->subMinute()]);

    Sanctum::actingAs($this->resident);

    expect($this->getJson(route('api.resident.map'))->assertOk()->json('gobikers'))->toBe([]);
});

it('falls back to the exact barangay when the viewer\'s barangay has no town', function () {
    Barangay::create(['name' => 'Sitio Nowhere', 'municipality' => '']);
    $this->resident->update(['barangay' => 'Sitio Nowhere']);
    $this->colleague->update(['barangay' => 'Sitio Nowhere']);
    onlineGobiker($this->colleague);
    onlineGobiker($this->gobiker);

    Sanctum::actingAs($this->resident);
    $map = $this->getJson(route('api.resident.map'))->assertOk()->json();

    expect(mapNames($map))->toBe(['Jose Lim'])
        ->and($map['town'])->toBeNull();
});

it('uses the barangay a GoBiker picked for their ronda as their town', function () {
    aguilarPoblacion();
    onlineGobiker($this->faraway);
    Location::where('user_id', $this->faraway->id)->update(['designated_barangay' => 'Poblacion (Aguilar)']);

    Sanctum::actingAs($this->resident);

    expect($this->getJson(route('api.resident.map'))->assertOk()->json('gobikers'))->toBe([]);
});

// ---------------------------------------------------------------- how good a position is

it('keeps how accurate a position is, and when the phone measured it', function () {
    $this->freezeSecond();
    Sanctum::actingAs($this->gobiker);

    $this->postJson(route('api.gobiker.location'), [
        'latitude' => 15.9566, 'longitude' => 120.1866, 'accuracy' => 8.5, 'speed' => 3.2, 'heading' => 90, 'age_seconds' => 4,
    ])->assertOk()->assertJsonPath('moved', true);

    $location = $this->gobiker->location;
    expect($location->accuracy)->toBe(8.5)
        ->and($location->speed)->toBe(3.2)
        // Measured four seconds before the server heard of it, by the server's own clock.
        ->and($location->position_at->equalTo(now()->subSeconds(4)))->toBeTrue()
        ->and($location->last_seen_at->equalTo(now()))->toBeTrue();

    $this->actingAs($this->admin, 'web')->getJson(route('admin.locations.index'))
        ->assertOk()
        ->assertJsonPath('0.accuracy', 8.5)
        ->assertJsonPath('0.position_at', now()->subSeconds(4)->toIso8601String());
});

it('does not move the dot for a rough or an old position, but keeps the GoBiker online', function (array $bad) {
    $this->freezeSecond();
    Sanctum::actingAs($this->gobiker);
    $this->postJson(route('api.gobiker.location'), ['latitude' => 15.9566, 'longitude' => 120.1866, 'accuracy' => 6])->assertOk();

    $this->travel(20)->seconds();
    // Indoors the phone falls back to a cell-tower guess, or to where it last knew it was.
    $this->postJson(route('api.gobiker.location'), $bad + ['latitude' => 15.99, 'longitude' => 120.25])
        ->assertOk()
        ->assertJsonPath('moved', false);

    $location = $this->gobiker->location;
    expect($location->latitude)->toBe(15.9566)
        ->and($location->accuracy)->toBe(6.0)
        // Still heard from: on the map, and still someone the admin can send.
        ->and($location->last_seen_at->equalTo(now()))->toBeTrue();
})->with([
    'a cell-tower guess' => [['accuracy' => 900]],
    'a last-known position from an hour ago' => [['accuracy' => 10, 'age_seconds' => 3600]],
]);

it('takes a rough first position, because a rough dot beats no dot', function () {
    Sanctum::actingAs($this->gobiker);

    $this->postJson(route('api.gobiker.location'), ['latitude' => 15.99, 'longitude' => 120.25, 'accuracy' => 900])
        ->assertOk()
        ->assertJsonPath('moved', true);

    expect($this->gobiker->location)->latitude->toBe(15.99)->accuracy->toBe(900.0);
});

it('never steps back to a position measured before the one on the map', function () {
    Sanctum::actingAs($this->gobiker);
    $this->postJson(route('api.gobiker.location'), ['latitude' => 15.9566, 'longitude' => 120.1866, 'accuracy' => 6, 'age_seconds' => 1])->assertOk();

    $this->travel(5)->seconds();
    // An older reading that arrived late.
    $this->postJson(route('api.gobiker.location'), ['latitude' => 15.9500, 'longitude' => 120.1800, 'accuracy' => 6, 'age_seconds' => 30])
        ->assertOk()
        ->assertJsonPath('moved', false);

    expect($this->gobiker->location->latitude)->toBe(15.9566);
});

it('still tracks an app from before it reported accuracy, and survives odd numbers', function () {
    Sanctum::actingAs($this->gobiker);
    $this->postJson(route('api.gobiker.location'), ['latitude' => 15.9566, 'longitude' => 120.1866])->assertOk();

    $this->travel(10)->seconds();
    // Phones report -1 for "no reading"; none of it may cost the GoBiker their place on the map.
    $this->postJson(route('api.gobiker.location'), [
        'latitude' => 15.9570, 'longitude' => 120.1870, 'accuracy' => -1, 'speed' => -1, 'heading' => -1, 'age_seconds' => -5,
    ])->assertOk()->assertJsonPath('moved', true);

    expect($this->gobiker->location)
        ->latitude->toBe(15.9570)
        ->accuracy->toBeNull()
        ->speed->toBeNull();
});

it('does not let a rough emergency position replace a good one', function () {
    Sanctum::actingAs($this->gobiker);
    $this->postJson(route('api.gobiker.location'), ['latitude' => 15.9566, 'longitude' => 120.1866, 'accuracy' => 6])->assertOk();

    $this->postJson(route('api.gobiker.emergency'), ['latitude' => 15.99, 'longitude' => 120.25, 'accuracy' => 1500])->assertOk();

    expect($this->gobiker->location)->status->toBe('emergency')->latitude->toBe(15.9566);
});

it('tells the admin and the GoBiker who was sent how far out an SOS pin may be', function () {
    $this->freezeSecond();
    onlineGobiker($this->gobiker);
    Sanctum::actingAs($this->resident);
    $id = $this->postJson(route('api.resident.sos.store'), [
        'address' => '12 Rizal St.', 'landmark' => 'Chapel',
        'latitude' => 15.95, 'longitude' => 120.18, 'accuracy' => 65.5, 'age_seconds' => 40,
    ])->assertCreated()->json('sos.id');

    $this->actingAs($this->admin, 'web')->getJson(route('admin.sos-pins.index'))
        ->assertOk()
        ->assertJsonPath('0.accuracy', 65.5)
        ->assertJsonPath('0.position_at', now()->subSeconds(40)->toIso8601String());

    SosRequest::findOrFail($id)->update(['status' => 'responding', 'assigned_gobiker_id' => $this->gobiker->id]);
    Sanctum::actingAs($this->gobiker);
    $this->getJson(route('api.gobiker.map'))->assertOk()->assertJsonPath('sos.0.accuracy', 65.5);
});

it('takes an SOS from an app that says nothing about how good its pin is', function () {
    Sanctum::actingAs($this->resident);
    $id = $this->postJson(route('api.resident.sos.store'), [
        'address' => '12 Rizal St.', 'landmark' => 'Chapel', 'latitude' => 15.95, 'longitude' => 120.18,
    ])->assertCreated()->json('sos.id');

    expect(SosRequest::findOrFail($id))->latitude->toBe(15.95)->location_accuracy->toBeNull();

    // No position at all: nothing is made up about one.
    $id = $this->postJson(route('api.resident.sos.store'), [
        'address' => '12 Rizal St.', 'landmark' => 'Chapel', 'accuracy' => 12,
    ])->assertCreated()->json('sos.id');

    expect(SosRequest::findOrFail($id))->latitude->toBeNull()->location_accuracy->toBeNull()->location_recorded_at->toBeNull();
});

// ---------------------------------------------------------------- SOS assignment

it('lets the admin send a GoBiker to an SOS', function () {
    onlineGobiker($this->gobiker);
    $sos = liveSos($this->resident);

    $this->actingAs($this->admin)
        ->patchJson(route('admin.operations.sos.status', $sos), ['status' => 'responding', 'gobiker_id' => $this->gobiker->id])
        ->assertOk()
        ->assertJsonPath('assigned_gobiker', 'Maria Santos');

    expect($sos->fresh())->status->toBe('responding')->assigned_gobiker_id->toBe($this->gobiker->id);
});

it('lets the admin send a GoBiker from another barangay, who then sees the request', function () {
    onlineGobiker($this->faraway, 16.05, 120.30);
    $sos = liveSos($this->resident);

    // The nearest GoBiker often covers the next barangay, so assignment is not limited to
    // the emergency's own. Being assigned is then the only thing that reveals the request.
    $this->actingAs($this->admin)
        ->patchJson(route('admin.operations.sos.status', $sos), ['status' => 'responding', 'gobiker_id' => $this->faraway->id])
        ->assertOk();

    Sanctum::actingAs($this->faraway);
    $this->getJson(route('api.gobiker.sos.index'))->assertOk()->assertJsonPath('sos.0.id', $sos->id);
    $this->getJson(route('api.gobiker.map'))->assertOk()->assertJsonPath('sos.0.id', $sos->id);
});

it('shows the GoBiker who was sent that the resident cancelled, as a closed request', function () {
    onlineGobiker($this->gobiker);
    $sos = liveSos($this->resident, ['status' => 'responding', 'assigned_gobiker_id' => $this->gobiker->id]);

    Sanctum::actingAs($this->resident);
    $this->postJson(route('api.resident.sos.cancel', $sos))->assertOk();

    Sanctum::actingAs($this->gobiker);
    // Gone from the list of emergencies they must answer...
    $this->getJson(route('api.gobiker.sos.index'))->assertOk()->assertJsonCount(0, 'sos');
    // ...and on the map it reads as closed, never as open (older apps know no "cancelled").
    $this->getJson(route('api.gobiker.map'))->assertOk()
        ->assertJsonPath('sos.0.id', $sos->id)
        ->assertJsonPath('sos.0.status', 'resolved')
        ->assertJsonPath('sos.0.cancelled', true);
});

it('does not let the admin send anyone but an active GoBiker', function () {
    $sos = liveSos($this->resident);
    $suspended = User::factory()->create(['role' => 'GoBiker', 'status' => 'Suspended', 'barangay' => 'Poblacion']);

    foreach ([$this->resident->id, $suspended->id] as $id) {
        $this->actingAs($this->admin)
            ->patchJson(route('admin.operations.sos.status', $sos), ['status' => 'responding', 'gobiker_id' => $id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('gobiker_id');
    }
});

it('refuses to send a GoBiker whose app is shut, however the request is made', function () {
    $sos = liveSos($this->resident);
    // Maria Santos is active and covers the barangay, but her app has not reported in.
    $this->actingAs($this->admin)
        ->patchJson(route('admin.operations.sos.status', $sos), ['status' => 'responding', 'gobiker_id' => $this->gobiker->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('gobiker_id')
        ->assertJsonPath('errors.gobiker_id.0', 'That GoBiker is offline. Send one whose app is on and showing on the map.');

    expect($sos->fresh())->assigned_gobiker_id->toBeNull()->status->toBe('sent');

    // The same GoBiker, once their app is reporting again.
    onlineGobiker($this->gobiker);
    $this->patchJson(route('admin.operations.sos.status', $sos), ['status' => 'responding', 'gobiker_id' => $this->gobiker->id])
        ->assertOk();

    expect($sos->fresh()->assigned_gobiker_id)->toBe($this->gobiker->id);
});

it('offers nobody when every GoBiker is offline, and the stale ones drop out', function () {
    $sos = liveSos($this->resident);

    $this->actingAs($this->admin)->getJson(route('admin.operations.sos.gobikers', $sos))
        ->assertOk()
        ->assertJsonCount(0, 'gobikers');

    onlineGobiker($this->gobiker);
    $this->getJson(route('admin.operations.sos.gobikers', $sos))->assertOk()->assertJsonCount(1, 'gobikers');

    // Their last position is now older than the staleness window: they are offline again.
    $this->gobiker->location->update(['last_seen_at' => now()->subSeconds(config('app.location_stale_after_seconds') + 5)]);
    $this->getJson(route('admin.operations.sos.gobikers', $sos))->assertOk()->assertJsonCount(0, 'gobikers');
});

it('lets the admin hand a responding SOS to another GoBiker', function () {
    onlineGobiker($this->colleague);
    $sos = liveSos($this->resident, ['status' => 'responding', 'assigned_gobiker_id' => $this->gobiker->id, 'responded_at' => now()]);

    $this->actingAs($this->admin)
        ->patchJson(route('admin.operations.sos.status', $sos), ['status' => 'responding', 'gobiker_id' => $this->colleague->id])
        ->assertOk()
        ->assertJsonPath('assigned_gobiker', 'Jose Lim');

    expect($sos->fresh())->assigned_gobiker_id->toBe($this->colleague->id);

    // The request moves with the assignment: the GoBiker taken off it loses access.
    Sanctum::actingAs($this->gobiker);
    $this->getJson(route('api.gobiker.sos.index'))->assertJsonCount(0, 'sos');
    $this->postJson(route('api.gobiker.sos.resolve', $sos))->assertForbidden();
});

it('lists the GoBikers who can respond, nearest first, and the SOS pins for the admin map', function () {
    onlineGobiker($this->colleague);
    onlineGobiker($this->faraway, 16.05, 120.30);
    $sos = liveSos($this->resident);
    liveSos($this->neighbor, ['latitude' => null, 'longitude' => null]);

    // The request's own barangay by default. Maria Santos covers it too but her app is shut,
    // so she is not offered: a closed app can never be told that someone needs help.
    $this->actingAs($this->admin)->getJson(route('admin.operations.sos.gobikers', $sos))
        ->assertOk()
        ->assertJsonPath('barangay', 'Poblacion')
        ->assertJsonPath('gobikers.0.name', 'Jose Lim')
        ->assertJsonPath('gobikers.0.distance_km', 0)
        ->assertJsonCount(1, 'gobikers');

    // Widened to the whole town: still only the online ones, nearest first.
    $this->getJson(route('admin.operations.sos.gobikers', $sos).'?barangay=all')
        ->assertOk()
        ->assertJsonPath('gobikers.0.name', 'Jose Lim')
        ->assertJsonPath('gobikers.1.name', 'Rico Dela')
        ->assertJsonCount(2, 'gobikers');

    // One barangay at a time, for picking a neighbouring responder.
    $this->getJson(route('admin.operations.sos.gobikers', $sos).'?barangay=Hacienda')
        ->assertOk()
        ->assertJsonCount(1, 'gobikers')
        ->assertJsonPath('gobikers.0.name', 'Rico Dela');

    $this->getJson(route('admin.sos-pins.index'))->assertOk()->assertJsonCount(1)->assertJsonPath('0.status', 'sent');
});

it('offers every barangay with its town for the picker, the request\'s own town first', function () {
    // A neighbouring town linked through the location tables, and one row only carrying the
    // old text column: both must say which town they are in.
    $province = Province::create(['name' => 'Pangasinan', 'region' => 'Region I']);
    $city = City::create(['province_id' => $province->id, 'name' => 'Pangasinan']);
    $aguilar = Municipality::create(['city_id' => $city->id, 'name' => 'Aguilar']);
    Barangay::create(['name' => 'Poblacion (Aguilar)', 'municipality' => 'Bugallon', 'municipality_id' => $aguilar->id]);
    Barangay::create(['name' => 'Bayaoas', 'municipality' => 'Aguilar', 'municipality_id' => $aguilar->id]);
    Barangay::create(['name' => 'Alos', 'municipality' => 'Alaminos']);

    $sos = liveSos($this->resident);
    $choices = $this->actingAs($this->admin)->getJson(route('admin.operations.sos.gobikers', $sos))
        ->assertOk()
        ->assertJsonPath('barangay', 'Poblacion')
        ->json('barangays');

    $own = collect($choices)->takeWhile(fn (array $b) => $b['town'] === 'Bugallon');

    expect($choices[0])->toHaveKeys(['name', 'town'])
        // The linked town wins over the stale text column.
        ->and(collect($choices)->firstWhere('name', 'Poblacion (Aguilar)')['town'])->toBe('Aguilar')
        // Every Bugallon barangay comes first, A to Z, and nothing else is among them.
        ->and($own->count())->toBe(Barangay::whereNull('municipality_id')->where('municipality', 'Bugallon')->count())
        ->and($own->pluck('name')->all())->toBe($own->pluck('name')->sortBy(fn ($n) => mb_strtolower($n))->values()->all())
        ->and($own->pluck('name'))->toContain('Poblacion', 'Hacienda')
        // Then the other towns A to Z, each A to Z within.
        ->and(collect($choices)->slice($own->count())->map(fn (array $b) => "{$b['town']}/{$b['name']}")->values()->all())
        ->toBe(['Aguilar/Bayaoas', 'Aguilar/Poblacion (Aguilar)', 'Alaminos/Alos']);
});

it('sends the face of whoever asked for help with their pin, and nothing at all when they have no photo', function () {
    liveSos($this->resident);

    // Most residents never add one: the map draws their initials instead.
    $this->actingAs($this->admin)->getJson(route('admin.sos-pins.index'))
        ->assertOk()
        ->assertJsonPath('0.resident', 'Ana Reyes')
        ->assertJsonPath('0.resident_photo', null);

    $this->resident->update(['photo_path' => 'profile-photos/ana.jpg']);

    $this->getJson(route('admin.sos-pins.index'))
        ->assertOk()
        ->assertJsonPath('0.resident_photo', fn ($url) => is_string($url) && str_contains($url, 'profile-photos/ana.jpg'));
});

it('takes a resolved emergency off the admin map, and leaves the others alone', function () {
    $first = liveSos($this->resident);
    $middle = liveSos($this->neighbor);
    $last = liveSos($this->resident);

    $this->actingAs($this->admin)->getJson(route('admin.sos-pins.index'))->assertOk()->assertJsonCount(3);

    $middle->update(['status' => 'resolved', 'resolved_at' => now(), 'resolved_by_gobiker_id' => $this->gobiker->id]);

    $ids = collect($this->getJson(route('admin.sos-pins.index'))->assertOk()->assertJsonCount(2)->json())->pluck('id');
    expect($ids)->toContain($first->id)->toContain($last->id)->not->toContain($middle->id);
});

it('hides a waiting SOS from every GoBiker until the admin assigns one', function () {
    $sos = liveSos($this->resident);

    foreach ([$this->gobiker, $this->colleague, $this->faraway] as $gobiker) {
        Sanctum::actingAs($gobiker);
        $this->getJson(route('api.gobiker.map'))->assertOk()->assertJsonCount(0, 'sos');
        $this->getJson(route('api.gobiker.sos.index'))->assertOk()->assertJsonCount(0, 'sos');
        $this->postJson(route('api.gobiker.sos.resolve', $sos))->assertForbidden();
    }
});

it('shows the assigned SOS on that GoBiker\'s map only, and only they can resolve it', function () {
    $sos = liveSos($this->resident, ['status' => 'responding', 'assigned_gobiker_id' => $this->gobiker->id]);

    Sanctum::actingAs($this->colleague);
    $this->getJson(route('api.gobiker.map'))->assertOk()->assertJsonCount(0, 'sos');
    $this->postJson(route('api.gobiker.sos.resolve', $sos))->assertForbidden();

    Sanctum::actingAs($this->gobiker);
    $this->getJson(route('api.gobiker.sos.index'))->assertOk()->assertJsonPath('sos.0.id', $sos->id);
    $this->postJson(route('api.gobiker.sos.resolve', $sos))->assertOk()->assertJsonPath('sos.status', 'resolved');
    $this->postJson(route('api.gobiker.sos.resolve', $sos))->assertUnprocessable();

    expect($sos->fresh())->resolved_by_gobiker_id->toBe($this->gobiker->id);
    // Still on the map for a while, now green.
    $this->getJson(route('api.gobiker.map'))->assertJsonPath('sos.0.status', 'resolved');
    $this->getJson(route('api.gobiker.sos.index'))->assertJsonCount(0, 'sos');
});

// ---------------------------------------------------------------- SOS decline

it('lets the assigned GoBiker decline with the required reason, and alerts the admin', function () {
    $sos = liveSos($this->resident, ['status' => 'responding', 'assigned_gobiker_id' => $this->gobiker->id]);

    Sanctum::actingAs($this->gobiker);
    $this->postJson(route('api.gobiker.sos.decline', $sos))->assertUnprocessable()
        ->assertJsonValidationErrors('reason');

    $this->postJson(route('api.gobiker.sos.decline', $sos), ['reason' => 'My bike has a flat tire'])
        ->assertOk()
        ->assertJsonPath('sos.status', 'declined')
        ->assertJsonPath('sos.status_label', 'Declined by GoBiker');

    expect($sos->fresh())
        ->status->toBe('declined')
        ->decline_reason->toBe('My bike has a flat tire')
        ->declined_by_gobiker_id->toBe($this->gobiker->id)
        ->declined_at->not->toBeNull()
        ->responded_at->not->toBeNull();

    // Off the GoBiker's list and map at once, still on the admin map, and the alarm rings
    // again with the reason in it.
    $this->getJson(route('api.gobiker.sos.index'))->assertJsonCount(0, 'sos');
    $this->getJson(route('api.gobiker.map'))->assertJsonCount(0, 'sos');

    $this->actingAs($this->resident)->getJson(route('api.resident.sos.index'))
        ->assertJsonPath('sos.0.status', 'declined');

    $alarm = $this->actingAs($this->admin, 'web')->getJson(route('admin.emergencies.index'))
        ->assertOk()->json('emergencies');
    expect($alarm)->toHaveCount(1)
        ->and($alarm[0]['declined'])->toBeTrue()
        ->and($alarm[0]['decline_reason'])->toBe('My bike has a flat tire')
        ->and($alarm[0]['declined_by'])->toBe('Maria Santos')
        ->and($alarm[0]['respond_url'])->toBe(route('admin.operations.sos.status', $sos));
});

it('does not let anyone decline a SOS but the GoBiker it is assigned to', function (string $status) {
    $sos = liveSos($this->resident, ['status' => $status, 'assigned_gobiker_id' => $this->gobiker->id]);

    Sanctum::actingAs($this->colleague);
    $this->postJson(route('api.gobiker.sos.decline', $sos), ['reason' => 'Not mine'])
        ->assertForbidden();
    expect($sos->fresh())->status->toBe($status)->decline_reason->toBeNull();
})->with(['sent', 'responding']);

it('refuses to decline a request that is already closed or declined', function (string $status) {
    $sos = liveSos($this->resident, [
        'status' => $status, 'assigned_gobiker_id' => $this->gobiker->id,
        'declined_at' => $status === 'declined' ? now() : null,
        'declined_by_gobiker_id' => $status === 'declined' ? $this->gobiker->id : null,
        'decline_reason' => $status === 'declined' ? 'First reason' : null,
    ]);

    Sanctum::actingAs($this->gobiker);
    $this->postJson(route('api.gobiker.sos.decline', $sos), ['reason' => 'Again'])
        ->assertUnprocessable();
    expect($sos->fresh())->status->toBe($status);
})->with(['resolved', 'cancelled', 'declined']);

it('lets the admin reassign a declined SOS to another GoBiker, who is then told', function () {
    $sos = liveSos($this->resident, [
        'status' => 'declined', 'assigned_gobiker_id' => $this->gobiker->id,
        'declined_at' => now(), 'declined_by_gobiker_id' => $this->gobiker->id,
        'decline_reason' => 'I am out of town',
    ]);
    onlineGobiker($this->colleague);

    $this->actingAs($this->admin, 'web')
        ->patchJson(route('admin.operations.sos.status', $sos), [
            'status' => 'responding', 'gobiker_id' => $this->colleague->id,
        ])->assertOk();

    expect($sos->fresh())
        ->status->toBe('responding')
        ->assigned_gobiker_id->toBe($this->colleague->id)
        // The old decline stays on the record for the admin's history.
        ->decline_reason->toBe('I am out of town');

    // The reassignment stopped the alarm, and the new GoBiker now sees it.
    $this->actingAs($this->admin, 'web')
        ->getJson(route('admin.emergencies.index'))->assertOk()->assertJsonCount(0, 'emergencies');
    $this->actingAs($this->colleague)
        ->getJson(route('api.gobiker.sos.index'))->assertOk()->assertJsonPath('sos.0.id', $sos->id);
});

it('keeps a declined SOS off the alarm once an admin resolves it', function () {
    $sos = liveSos($this->resident, [
        'status' => 'declined', 'assigned_gobiker_id' => $this->gobiker->id,
        'declined_at' => now(), 'declined_by_gobiker_id' => $this->gobiker->id,
        'decline_reason' => 'Too far away',
    ]);

    $this->actingAs($this->admin, 'web')
        ->patchJson(route('admin.operations.sos.status', $sos), ['status' => 'resolved'])->assertOk();

    expect($sos->fresh())->status->toBe('resolved');
    $this->actingAs($this->admin, 'web')
        ->getJson(route('admin.emergencies.index'))->assertOk()->assertJsonCount(0, 'emergencies');
});

it('shows the decline reason on the admin SOS pages', function () {
    $sos = liveSos($this->resident, [
        'status' => 'declined', 'assigned_gobiker_id' => $this->gobiker->id,
        'declined_at' => now(), 'declined_by_gobiker_id' => $this->gobiker->id,
        'decline_reason' => 'My bike has a flat tire',
    ]);

    $this->actingAs($this->admin, 'web')
        ->get(route('admin.operations.sos.show', $sos))
        ->assertOk()
        ->assertSee('Why the GoBiker declined')
        ->assertSee('Maria Santos')
        ->assertSee('My bike has a flat tire')
        ->assertSee('declined it');

    $this->actingAs($this->admin, 'web')
        ->get(route('admin.operations.sos.index', ['status' => 'declined']))
        ->assertOk()
        ->assertSee('Ana Reyes');

    $this->actingAs($this->admin, 'web')
        ->getJson(route('admin.sos-pins.index'))
        ->assertOk()
        ->assertJsonPath('0.status', 'declined')
        ->assertJsonPath('0.declined_by', 'Maria Santos')
        ->assertJsonPath('0.decline_reason', 'My bike has a flat tire');
});

it('tells the resident who is responding to their SOS', function () {
    $sos = liveSos($this->resident);

    Sanctum::actingAs($this->resident);
    $this->getJson(route('api.resident.sos.index'))->assertOk()->assertJsonPath('sos.0.responder', null);

    $sos->update(['status' => 'responding', 'assigned_gobiker_id' => $this->faraway->id, 'responded_at' => now()]);

    // A push says the name too, but a push can be missed or switched off.
    $this->getJson(route('api.resident.sos.index'))
        ->assertOk()
        ->assertJsonPath('sos.0.responder.name', 'Rico Dela')
        ->assertJsonPath('sos.0.responder.barangay', 'Hacienda');
});

// ---------------------------------------------------------------- feedback

it('asks the resident to rate the GoBiker who resolved their SOS, once', function () {
    $sos = liveSos($this->resident, [
        'status' => 'resolved', 'resolved_at' => now(), 'assigned_gobiker_id' => $this->gobiker->id,
        'resolved_by_gobiker_id' => $this->gobiker->id,
    ]);

    Sanctum::actingAs($this->resident);
    $this->getJson(route('api.resident.feedback.pending'))
        ->assertOk()
        ->assertJsonPath('pending.kind', 'sos')
        ->assertJsonPath('pending.subject_id', $sos->id)
        ->assertJsonPath('pending.gobiker.name', 'Maria Santos');

    $this->postJson(route('api.resident.feedback.store'), ['kind' => 'sos', 'subject_id' => $sos->id, 'rating' => 6])
        ->assertJsonValidationErrors('rating');
    $this->postJson(route('api.resident.feedback.store'), ['kind' => 'sos', 'subject_id' => $sos->id, 'rating' => 5, 'comment' => 'Fast!'])
        ->assertCreated();
    $this->postJson(route('api.resident.feedback.store'), ['kind' => 'sos', 'subject_id' => $sos->id, 'rating' => 1])
        ->assertUnprocessable();

    expect(Feedback::first())->gobiker_id->toBe($this->gobiker->id)->rating->toBe(5);
    $this->getJson(route('api.resident.feedback.pending'))->assertJsonPath('pending', null);
});

it('does not ask about an SOS the resident or the admin closed', function () {
    liveSos($this->resident, ['status' => 'resolved', 'resolved_at' => now(), 'resolved_by_resident' => true]);

    Sanctum::actingAs($this->resident);
    $this->getJson(route('api.resident.feedback.pending'))->assertJsonPath('pending', null);
});

it('asks the resident to rate a check-up, and a skip counts as an answer', function () {
    $checkup = Patient::create([
        'user_id' => $this->gobiker->id, 'resident_id' => $this->resident->id, 'barangay' => 'Poblacion',
        'name' => 'Ana Reyes', 'address' => 'Purok 3', 'contact' => '09170001111', 'age' => 40,
        'sys' => 120, 'dia' => 80, 'pulse' => 72, 'resp' => 16, 'temp' => 36.6,
    ]);

    Sanctum::actingAs($this->resident);
    $this->getJson(route('api.resident.feedback.pending'))
        ->assertJsonPath('pending.kind', 'checkup')
        ->assertJsonPath('pending.gobiker.name', 'Maria Santos');

    $this->postJson(route('api.resident.feedback.store'), ['kind' => 'checkup', 'subject_id' => $checkup->id])->assertCreated();

    expect(Feedback::first()->rating)->toBeNull();
    $this->getJson(route('api.resident.feedback.pending'))->assertJsonPath('pending', null);
});

it('does not let a resident rate someone else\'s SOS', function () {
    $sos = liveSos($this->neighbor, ['status' => 'resolved', 'resolved_at' => now(), 'resolved_by_gobiker_id' => $this->gobiker->id]);

    Sanctum::actingAs($this->resident);
    $this->postJson(route('api.resident.feedback.store'), ['kind' => 'sos', 'subject_id' => $sos->id, 'rating' => 4])->assertNotFound();
});
