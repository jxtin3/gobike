<?php

use App\Models\Feedback;
use App\Models\Location;
use App\Models\Patient;
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

it('shows a resident only the online GoBikers of their barangay', function () {
    onlineGobiker($this->gobiker);
    onlineGobiker($this->faraway);

    Sanctum::actingAs($this->resident);
    $map = $this->getJson(route('api.resident.map'))->assertOk()->json();

    expect(collect($map['gobikers'])->pluck('name')->all())->toBe(['Maria Santos'])
        ->and($map)->not->toHaveKey('residents');
});

it('never plots residents for a GoBiker, only the GoBikers of their barangay', function () {
    onlineGobiker($this->colleague);
    onlineGobiker($this->faraway);

    Sanctum::actingAs($this->gobiker);
    $map = $this->getJson(route('api.gobiker.map'))->assertOk()->json();

    // A GoBiker picks their own barangay, so plotting its residents would let anyone
    // watch residents anywhere in the town. Nobody is plotted but fellow GoBikers.
    expect($map)->not->toHaveKey('residents')
        ->and(collect($map['gobikers'])->pluck('name')->all())->toBe(['Jose Lim']);
});

// ---------------------------------------------------------------- SOS assignment

it('lets the admin send a GoBiker to an SOS', function () {
    $sos = liveSos($this->resident);

    $this->actingAs($this->admin)
        ->patchJson(route('admin.operations.sos.status', $sos), ['status' => 'responding', 'gobiker_id' => $this->gobiker->id])
        ->assertOk()
        ->assertJsonPath('assigned_gobiker', 'Maria Santos');

    expect($sos->fresh())->status->toBe('responding')->assigned_gobiker_id->toBe($this->gobiker->id);
});

it('lets the admin send a GoBiker from another barangay, who then sees the request', function () {
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

it('lets the admin hand a responding SOS to another GoBiker', function () {
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

    // The request's own barangay by default.
    $this->actingAs($this->admin)->getJson(route('admin.operations.sos.gobikers', $sos))
        ->assertOk()
        ->assertJsonPath('barangay', 'Poblacion')
        ->assertJsonPath('gobikers.0.name', 'Jose Lim')
        ->assertJsonPath('gobikers.0.distance_km', 0)
        ->assertJsonCount(2, 'gobikers');

    // Widened to the whole town: online first, then whoever is really nearest.
    $this->getJson(route('admin.operations.sos.gobikers', $sos).'?barangay=all')
        ->assertOk()
        ->assertJsonPath('gobikers.0.name', 'Jose Lim')
        ->assertJsonPath('gobikers.1.name', 'Rico Dela')
        ->assertJsonPath('gobikers.2.name', 'Maria Santos')
        ->assertJsonCount(3, 'gobikers');

    // One barangay at a time, for picking a neighbouring responder.
    $this->getJson(route('admin.operations.sos.gobikers', $sos).'?barangay=Hacienda')
        ->assertOk()
        ->assertJsonCount(1, 'gobikers')
        ->assertJsonPath('gobikers.0.name', 'Rico Dela');

    $this->getJson(route('admin.sos-pins.index'))->assertOk()->assertJsonCount(1)->assertJsonPath('0.status', 'sent');
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
