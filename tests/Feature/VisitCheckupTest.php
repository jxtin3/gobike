<?php

use App\Models\Appointment;
use App\Models\DeviceToken;
use App\Models\Patient;
use App\Models\User;
use App\Services\Push\FcmSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/** Records the messages instead of calling Firebase. */
class VisitCheckupFakeSender implements FcmSender
{
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
}

beforeEach(function () {
    $this->withoutDefer();
    $this->push = new VisitCheckupFakeSender;
    $this->app->instance(FcmSender::class, $this->push);

    $this->gobiker = User::factory()->create([
        'name' => 'Maria Santos', 'role' => 'GoBiker', 'status' => 'Active', 'barangay' => 'Poblacion',
    ]);
    $this->resident = User::factory()->create([
        'name' => 'Ana Reyes', 'role' => 'User', 'status' => 'Active', 'barangay' => 'Poblacion',
        'mobile' => '09170001111', 'birthdate' => now()->subYears(61)->subDays(3)->toDateString(),
    ]);
    DeviceToken::create(['user_id' => $this->resident->id, 'token' => 'resident-phone']);
});

/** An accepted visit of $resident with $gobiker, as a GoBiker would find it in My visits. */
function acceptedVisit(User $resident, User $gobiker, array $overrides = []): Appointment
{
    return Appointment::create($overrides + [
        'resident_id' => $resident->id,
        'gobiker_id' => $gobiker->id,
        'barangay' => 'Poblacion',
        'address' => 'Purok 3, near the chapel',
        'preferred_date' => now()->addDay()->toDateString(),
        'preferred_window' => 'morning',
        'reason' => 'Blood pressure check',
        'status' => 'accepted',
        'accepted_at' => now(),
    ]);
}

function vitals(array $overrides = []): array
{
    return $overrides + ['sys' => 128, 'dia' => 84, 'pulse' => 76, 'resp' => 16, 'temp' => 36.7];
}

it('records the check-up with the resident\'s own details and finishes the visit', function () {
    $visit = acceptedVisit($this->resident, $this->gobiker);

    Sanctum::actingAs($this->gobiker);
    // Anything the phone sends about the person is ignored: it all comes from the resident.
    $this->postJson(route('api.gobiker.appointments.checkup', $visit), vitals([
        'blood_sugar' => 105, 'remarks' => 'Drink more water.',
        'name' => 'Someone Else', 'address' => 'Elsewhere', 'contact' => '09990000000', 'age' => 10,
    ]))
        ->assertCreated()
        ->assertJsonPath('appointment.status', 'completed')
        ->assertJsonPath('patient.name', 'Ana Reyes')
        ->assertJsonPath('patient.contact', '09170001111')
        ->assertJsonPath('patient.address', 'Purok 3, near the chapel')
        ->assertJsonPath('patient.age', 61)
        ->assertJsonPath('patient.barangay', 'Poblacion')
        ->assertJsonPath('patient.sys', 128)
        ->assertJsonPath('patient.blood_sugar', 105)
        ->assertJsonPath('patient.remarks', 'Drink more water.');

    $patient = Patient::sole();
    expect($patient->user_id)->toBe($this->gobiker->id)
        ->and($patient->resident_id)->toBe($this->resident->id);

    $visit->refresh();
    expect($visit->status)->toBe('completed')
        ->and($visit->patient_id)->toBe($patient->id)
        ->and($visit->completed_at)->not->toBeNull();
});

it('shows the resident the check-up and tells them once', function () {
    $visit = acceptedVisit($this->resident, $this->gobiker);

    Sanctum::actingAs($this->gobiker);
    $this->postJson(route('api.gobiker.appointments.checkup', $visit), vitals())->assertCreated();

    expect(collect($this->push->sent)->pluck('title')->all())->toBe(['Home visit done']);

    Sanctum::actingAs($this->resident);
    $this->getJson(route('api.resident.visits'))
        ->assertOk()
        ->assertJsonCount(1, 'visits')
        ->assertJsonPath('visits.0.sys', 128);
});

it('asks for the age only when the resident gave no birthdate', function () {
    $this->resident->update(['birthdate' => null]);
    $visit = acceptedVisit($this->resident, $this->gobiker);

    Sanctum::actingAs($this->gobiker);
    $this->postJson(route('api.gobiker.appointments.checkup', $visit), vitals())
        ->assertUnprocessable()
        ->assertJsonValidationErrors('age');
    expect(Patient::count())->toBe(0)->and($visit->fresh()->status)->toBe('accepted');

    $this->postJson(route('api.gobiker.appointments.checkup', $visit), vitals(['age' => 47]))
        ->assertCreated()
        ->assertJsonPath('patient.age', 47);
});

it('refuses readings that make no sense, and changes nothing', function () {
    $visit = acceptedVisit($this->resident, $this->gobiker);

    Sanctum::actingAs($this->gobiker);
    $this->postJson(route('api.gobiker.appointments.checkup', $visit), vitals(['sys' => 20, 'temp' => 'warm']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['sys', 'temp']);
    $this->postJson(route('api.gobiker.appointments.checkup', $visit), ['sys' => 120])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['dia', 'pulse', 'resp', 'temp']);

    expect(Patient::count())->toBe(0)->and($visit->fresh()->status)->toBe('accepted');
});

it('only lets the assigned GoBiker record it, and only at an accepted visit', function () {
    $visit = acceptedVisit($this->resident, $this->gobiker);
    $other = User::factory()->create(['role' => 'GoBiker', 'status' => 'Active', 'barangay' => 'Poblacion']);

    Sanctum::actingAs($other);
    $this->postJson(route('api.gobiker.appointments.checkup', $visit), vitals())->assertForbidden();

    Sanctum::actingAs($this->resident);
    $this->postJson(route('api.gobiker.appointments.checkup', $visit), vitals())->assertForbidden();

    foreach (['pending', 'declined', 'cancelled', 'completed'] as $status) {
        $visit->update(['status' => $status]);
        Sanctum::actingAs($this->gobiker);
        $this->postJson(route('api.gobiker.appointments.checkup', $visit), vitals())->assertUnprocessable();
    }
    expect(Patient::count())->toBe(0);
});

it('records one check-up per visit, however many times it is sent', function () {
    $visit = acceptedVisit($this->resident, $this->gobiker);

    Sanctum::actingAs($this->gobiker);
    $this->postJson(route('api.gobiker.appointments.checkup', $visit), vitals())->assertCreated();
    $this->postJson(route('api.gobiker.appointments.checkup', $visit), vitals())->assertUnprocessable();

    expect(Patient::count())->toBe(1);
});

it('gives the GoBiker the resident\'s age to fill the form with, and nobody else', function () {
    $visit = acceptedVisit($this->resident, $this->gobiker);

    Sanctum::actingAs($this->gobiker);
    $this->getJson(route('api.gobiker.appointments.index'))
        ->assertJsonPath('mine.0.resident_age', 61)
        ->assertJsonPath('mine.0.resident_name', 'Ana Reyes')
        ->assertJsonPath('mine.0.resident_mobile', '09170001111');

    $this->resident->update(['birthdate' => null]);
    $this->getJson(route('api.gobiker.appointments.index'))->assertJsonPath('mine.0.resident_age', null);

    Sanctum::actingAs($this->resident);
    $this->getJson(route('api.resident.appointments.index'))
        ->assertOk()
        ->assertJsonMissingPath('appointments.0.resident_age');
});

it('lets the GoBiker withdraw from an accepted visit, which the resident can then redo', function () {
    $visit = acceptedVisit($this->resident, $this->gobiker);

    Sanctum::actingAs($this->gobiker);
    $this->postJson(route('api.gobiker.appointments.decline', $visit), ['note' => 'My bike broke down.'])
        ->assertOk()
        ->assertJsonPath('appointment.status', 'declined')
        ->assertJsonPath('appointment.decline_note', 'My bike broke down.');

    expect(collect($this->push->sent)->pluck('title')->all())->toBe(['Home visit declined']);

    // It is over now: no check-up, and it cannot be declined again.
    $this->postJson(route('api.gobiker.appointments.checkup', $visit), vitals())->assertUnprocessable();
    $this->postJson(route('api.gobiker.appointments.decline', $visit))->assertUnprocessable();

    // A finished visit cannot be withdrawn from.
    $done = acceptedVisit($this->resident, $this->gobiker, ['status' => 'completed']);
    $this->postJson(route('api.gobiker.appointments.decline', $done))->assertUnprocessable();
});
