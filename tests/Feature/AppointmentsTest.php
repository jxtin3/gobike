<?php

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->gobiker = User::factory()->create([
        'name' => 'Maria Santos', 'role' => 'GoBiker', 'status' => 'Active', 'barangay' => 'Poblacion',
    ]);
    $this->resident = User::factory()->create([
        'name' => 'Ana Reyes', 'role' => 'User', 'status' => 'Active', 'barangay' => 'Poblacion', 'mobile' => '09170001111',
    ]);
});

function visitPayload(array $overrides = []): array
{
    return array_merge([
        'barangay' => 'Poblacion',
        'address' => 'Purok 3, near the chapel',
        'preferred_date' => now()->addDays(2)->toDateString(),
        'preferred_window' => 'morning',
        'reason' => 'Blood pressure check for my mother.',
    ], $overrides);
}

function requestVisit(User $resident, array $overrides = []): Appointment
{
    Sanctum::actingAs($resident);
    $id = test()->postJson(route('api.resident.appointments.store'), visitPayload($overrides))->assertCreated()->json('appointment.id');

    return Appointment::findOrFail($id);
}

// ---------------------------------------------------------------- resident

it('lets a resident request one visit at a time', function () {
    requestVisit($this->resident);

    $this->postJson(route('api.resident.appointments.store'), visitPayload())
        ->assertUnprocessable()
        ->assertJsonPath('message', 'You already have a visit request. Cancel it first to ask for a new one.');
});

it('rejects a visit in the past or too far ahead', function (int $days) {
    Sanctum::actingAs($this->resident);

    $this->postJson(route('api.resident.appointments.store'), visitPayload(['preferred_date' => now()->addDays($days)->toDateString()]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('preferred_date');
})->with(['yesterday' => -1, 'in 31 days' => 31]);

it('lets the resident cancel and ask again', function () {
    $visit = requestVisit($this->resident);

    $this->postJson(route('api.resident.appointments.cancel', $visit))->assertOk()->assertJsonPath('appointment.status', 'cancelled');
    $this->postJson(route('api.resident.appointments.store'), visitPayload())->assertCreated();
});

it('does not let anyone cancel another resident\'s visit', function () {
    $visit = requestVisit($this->resident);
    Sanctum::actingAs(User::factory()->create(['role' => 'User', 'status' => 'Active']));

    $this->postJson(route('api.resident.appointments.cancel', $visit))->assertForbidden();
});

// ---------------------------------------------------------------- GoBiker

it('shows a GoBiker only the requests in their barangay', function () {
    requestVisit($this->resident);
    requestVisit(User::factory()->create(['role' => 'User', 'status' => 'Active']), ['barangay' => 'Hacienda']);

    Sanctum::actingAs($this->gobiker);
    $this->getJson(route('api.gobiker.appointments.index'))
        ->assertOk()
        ->assertJsonCount(1, 'available')
        ->assertJsonPath('available.0.resident_name', 'Ana Reyes')
        ->assertJsonPath('available.0.resident_mobile', '09170001111')
        ->assertJsonPath('counts.available', 1);
});

it('lets only one GoBiker accept a request', function () {
    $visit = requestVisit($this->resident);
    $other = User::factory()->create(['role' => 'GoBiker', 'status' => 'Active', 'barangay' => 'Poblacion']);

    Sanctum::actingAs($this->gobiker);
    $this->postJson(route('api.gobiker.appointments.accept', $visit))->assertOk()->assertJsonPath('appointment.status', 'accepted');

    Sanctum::actingAs($other);
    $this->postJson(route('api.gobiker.appointments.accept', $visit))->assertStatus(409);

    expect($visit->fresh()->gobiker_id)->toBe($this->gobiker->id);

    Sanctum::actingAs($this->resident);
    $this->getJson(route('api.resident.appointments.index'))->assertJsonPath('appointments.0.gobiker_name', 'Maria Santos');
});

it('keeps GoBikers out of other barangays', function () {
    $visit = requestVisit($this->resident, ['barangay' => 'Hacienda']);

    Sanctum::actingAs($this->gobiker);
    $this->postJson(route('api.gobiker.appointments.accept', $visit))->assertForbidden();
});

it('marks a visit done with the GoBiker\'s own check-up only', function () {
    $visit = requestVisit($this->resident);
    Sanctum::actingAs($this->gobiker);
    $this->postJson(route('api.gobiker.appointments.accept', $visit))->assertOk();

    $checkup = Patient::create([
        'user_id' => $this->gobiker->id, 'name' => 'Ana Reyes', 'contact' => '09170001111', 'address' => 'Purok 3', 'age' => 60,
        'sys' => 130, 'dia' => 85, 'pulse' => 75, 'resp' => 16, 'temp' => 36.6, 'recorded_at' => now(),
    ]);
    $someoneElses = Patient::create([...$checkup->only(['name', 'contact', 'address', 'age', 'sys', 'dia', 'pulse', 'resp', 'temp', 'recorded_at']),
        'user_id' => User::factory()->create(['role' => 'GoBiker'])->id]);

    $this->postJson(route('api.gobiker.appointments.complete', $visit), ['patient_id' => $someoneElses->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('patient_id');

    $this->postJson(route('api.gobiker.appointments.complete', $visit), ['patient_id' => $checkup->id])
        ->assertOk()
        ->assertJsonPath('appointment.status', 'completed');

    expect($visit->fresh()->patient_id)->toBe($checkup->id);
});

// ------------------------------------------------- choosing a GoBiker

it('lists the GoBikers of the resident\'s barangay, or of another one', function () {
    User::factory()->create(['name' => 'Jose Cruz', 'role' => 'GoBiker', 'status' => 'Active', 'barangay' => 'Hacienda']);
    User::factory()->create(['name' => 'Not Approved', 'role' => 'GoBiker', 'status' => 'Inactive', 'barangay' => 'Poblacion']);
    Sanctum::actingAs($this->resident);

    $this->getJson(route('api.resident.gobikers'))
        ->assertOk()
        ->assertJsonPath('barangay', 'Poblacion')
        ->assertJsonCount(1, 'gobikers')
        ->assertJsonPath('gobikers.0.name', 'Maria Santos')
        ->assertJsonPath('gobikers.0.visits_done', 0);

    $this->getJson(route('api.resident.gobikers', ['barangay' => 'Hacienda']))
        ->assertOk()
        ->assertJsonCount(1, 'gobikers')
        ->assertJsonPath('gobikers.0.name', 'Jose Cruz');
});

it('sends the request to the chosen GoBiker only', function () {
    $neighbor = User::factory()->create(['role' => 'GoBiker', 'status' => 'Active', 'barangay' => 'Poblacion']);
    $visit = requestVisit($this->resident, ['gobiker_id' => $this->gobiker->id]);

    expect($visit)->status->toBe('pending')->gobiker_id->toBe($this->gobiker->id);

    Sanctum::actingAs($this->gobiker);
    $this->getJson(route('api.gobiker.appointments.index'))
        ->assertJsonCount(1, 'requests')
        ->assertJsonPath('requests.0.resident_name', 'Ana Reyes')
        ->assertJsonPath('counts.requests', 1)
        ->assertJsonCount(0, 'available');

    Sanctum::actingAs($neighbor);
    $this->getJson(route('api.gobiker.appointments.index'))->assertJsonCount(0, 'requests')->assertJsonCount(0, 'available');
    $this->postJson(route('api.gobiker.appointments.accept', $visit))->assertForbidden();

    Sanctum::actingAs($this->gobiker);
    $this->postJson(route('api.gobiker.appointments.accept', $visit))->assertOk()->assertJsonPath('appointment.status', 'accepted');
});

it('lets the resident choose a GoBiker from another barangay', function () {
    $elsewhere = User::factory()->create(['name' => 'Jose Cruz', 'role' => 'GoBiker', 'status' => 'Active', 'barangay' => 'Hacienda']);
    $visit = requestVisit($this->resident, ['gobiker_id' => $elsewhere->id]);

    Sanctum::actingAs($elsewhere);
    $this->postJson(route('api.gobiker.appointments.accept', $visit))->assertOk();

    Sanctum::actingAs($this->resident);
    $this->getJson(route('api.resident.appointments.index'))
        ->assertJsonPath('appointments.0.gobiker_name', 'Jose Cruz')
        ->assertJsonPath('appointments.0.gobiker_barangay', 'Hacienda');
});

it('does not accept a GoBiker who is not approved', function () {
    $inactive = User::factory()->create(['role' => 'GoBiker', 'status' => 'Inactive', 'barangay' => 'Poblacion']);
    Sanctum::actingAs($this->resident);

    $this->postJson(route('api.resident.appointments.store'), visitPayload(['gobiker_id' => $inactive->id]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('gobiker_id');
});

it('lets the chosen GoBiker decline, so the resident can ask someone else', function () {
    $visit = requestVisit($this->resident, ['gobiker_id' => $this->gobiker->id]);
    $other = User::factory()->create(['role' => 'GoBiker', 'status' => 'Active', 'barangay' => 'Poblacion']);

    Sanctum::actingAs($other);
    $this->postJson(route('api.gobiker.appointments.decline', $visit))->assertForbidden();

    Sanctum::actingAs($this->gobiker);
    $this->postJson(route('api.gobiker.appointments.decline', $visit))
        ->assertOk()
        ->assertJsonPath('appointment.status', 'declined')
        ->assertJsonPath('appointment.decline_note', "Maria Santos can't make this visit. You can ask another GoBiker.");
    $this->postJson(route('api.gobiker.appointments.decline', $visit))->assertUnprocessable();

    // The resident is free to send a new request to another GoBiker.
    requestVisit($this->resident, ['gobiker_id' => $other->id]);
});

it('keeps the GoBiker\'s own reason when declining', function () {
    $visit = requestVisit($this->resident, ['gobiker_id' => $this->gobiker->id]);

    Sanctum::actingAs($this->gobiker);
    $this->postJson(route('api.gobiker.appointments.decline', $visit), ['note' => 'I am away that day.'])
        ->assertJsonPath('appointment.decline_note', 'I am away that day.');
});

// ---------------------------------------------------------------- admin

it('shows the admin the chosen GoBiker, and lets the admin decline with a note', function () {
    $visit = requestVisit($this->resident, ['gobiker_id' => $this->gobiker->id]);
    $this->actingAs($this->admin, 'web');

    $this->get(route('admin.operations.appointments.index'))->assertOk()->assertSee('Ana Reyes');
    $this->get(route('admin.operations.appointments.show', $visit))->assertOk()
        ->assertSee('Maria Santos · Poblacion')
        ->assertSee('waiting for them to accept')
        ->assertDontSee('Assign');

    $this->patch(route('admin.operations.appointments.decline', $visit), ['decline_note' => 'No GoBiker that day.'])->assertSessionHasNoErrors();
    expect($visit->fresh())->status->toBe('declined')->decline_note->toBe('No GoBiker that day.');

    Sanctum::actingAs($this->resident);
    $this->getJson(route('api.resident.appointments.index'))->assertJsonPath('appointments.0.decline_note', 'No GoBiker that day.');
});

it('is for admins only', function () {
    $this->actingAs($this->gobiker, 'web')->get(route('admin.operations.appointments.index'))->assertForbidden();
});
