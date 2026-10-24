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

// ---------------------------------------------------------------- schedule

it('sends the GoBiker schedule set by the admin to the app', function () {
    $this->actingAs($this->admin, 'web')
        ->put(route('admin.operations.users.update', $this->gobiker), [
            'name' => 'Maria Santos', 'email' => $this->gobiker->email, 'role' => 'GoBiker',
            'schedule_days' => ['Fri', 'Mon', 'Wed'], 'schedule_start' => '08:00', 'schedule_end' => '12:00',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    Sanctum::actingAs($this->gobiker->fresh());
    $this->getJson('/api/mobile/me')
        ->assertOk()
        ->assertJsonPath('user.schedule', ['days' => ['Mon', 'Wed', 'Fri'], 'start' => '08:00', 'end' => '12:00']);
});

it('sends no schedule when none is set, or to residents', function () {
    Sanctum::actingAs($this->gobiker);
    $this->getJson('/api/mobile/me')->assertJsonPath('user.schedule', null);

    Sanctum::actingAs($this->resident);
    $this->getJson('/api/mobile/me')->assertJsonPath('user.schedule', null);
});

it('rejects a schedule that ends before it starts or has no times', function (array $schedule, string $field) {
    $this->actingAs($this->admin, 'web')
        ->put(route('admin.operations.users.update', $this->gobiker), [
            'name' => 'Maria Santos', 'email' => $this->gobiker->email, 'role' => 'GoBiker', ...$schedule,
        ])
        ->assertSessionHasErrors($field);
})->with([
    'ends first' => [['schedule_days' => ['Mon'], 'schedule_start' => '13:00', 'schedule_end' => '09:00'], 'schedule_end'],
    'no times' => [['schedule_days' => ['Mon']], 'schedule_start'],
    'bad day' => [['schedule_days' => ['Funday'], 'schedule_start' => '08:00', 'schedule_end' => '12:00'], 'schedule_days.0'],
]);

it('clears the schedule when no day is ticked', function () {
    $this->gobiker->update(['schedule_days' => ['Mon'], 'schedule_start' => '08:00', 'schedule_end' => '12:00']);

    $this->actingAs($this->admin, 'web')
        ->put(route('admin.operations.users.update', $this->gobiker), [
            'name' => 'Maria Santos', 'email' => $this->gobiker->email, 'role' => 'GoBiker',
            'schedule_start' => '', 'schedule_end' => '',
        ])
        ->assertSessionHasNoErrors();

    expect($this->gobiker->fresh()->schedule())->toBeNull();
});

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

// ---------------------------------------------------------------- admin

it('lets the admin assign a GoBiker or decline with a note', function () {
    $visit = requestVisit($this->resident);
    $this->actingAs($this->admin, 'web');

    $this->get(route('admin.operations.appointments.index'))->assertOk()->assertSee('Ana Reyes');
    $this->get(route('admin.operations.appointments.show', $visit))->assertOk()->assertSee('Maria Santos · Poblacion (same barangay)');

    $this->patch(route('admin.operations.appointments.assign', $visit), ['gobiker_id' => $this->gobiker->id])->assertSessionHasNoErrors();
    expect($visit->fresh())->status->toBe('accepted')->gobiker_id->toBe($this->gobiker->id);

    $this->patch(route('admin.operations.appointments.decline', $visit), ['decline_note' => 'No GoBiker that day.'])->assertSessionHasNoErrors();
    expect($visit->fresh())->status->toBe('declined')->decline_note->toBe('No GoBiker that day.');

    Sanctum::actingAs($this->resident);
    $this->getJson(route('api.resident.appointments.index'))->assertJsonPath('appointments.0.decline_note', 'No GoBiker that day.');
});

it('is for admins only', function () {
    $this->actingAs($this->gobiker, 'web')->get(route('admin.operations.appointments.index'))->assertForbidden();
});
