<?php

use App\Models\Patient;
use App\Models\PatientFollowUp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function linkGobiker(): User
{
    return User::factory()->create(['role' => 'GoBiker', 'status' => 'Active']);
}

function linkResident(array $attributes = []): User
{
    return User::factory()->create($attributes + [
        'name' => 'Lola Nena', 'role' => 'User', 'status' => 'Active', 'mobile' => '09181112222', 'barangay' => 'Hacienda',
    ]);
}

function linkCheckup(array $overrides = []): array
{
    return $overrides + [
        'name' => 'Lola Nena', 'address' => 'Purok 2', 'contact' => '09181112222', 'age' => 72,
        'sys' => 120, 'dia' => 78, 'pulse' => 70, 'resp' => 16, 'temp' => 36.5, 'barangay' => 'Hacienda',
    ];
}

it('links a new check-up to the resident with the same name and mobile', function () {
    $resident = linkResident();
    Sanctum::actingAs(linkGobiker());

    $id = $this->postJson(route('api.gobiker.patients.store'), linkCheckup(['name' => '  lola nena ']))
        ->assertCreated()
        ->json('patient.id');

    expect(Patient::find($id)->resident_id)->toBe($resident->id);
});

it('leaves a check-up unlinked when nobody has that name and mobile', function () {
    linkResident(['mobile' => '09000000000']);
    Sanctum::actingAs(linkGobiker());

    $id = $this->postJson(route('api.gobiker.patients.store'), linkCheckup())->assertCreated()->json('patient.id');

    expect(Patient::find($id)->resident_id)->toBeNull();
});

it('gives a resident who signs up later the check-ups recorded before', function () {
    Sanctum::actingAs(linkGobiker());
    $this->postJson(route('api.gobiker.patients.store'), linkCheckup())->assertCreated();

    $this->postJson('/api/mobile/register', [
        'name' => 'Lola Nena', 'email' => 'nena@example.com', 'mobile' => '09181112222',
        'barangay' => 'Hacienda', 'role' => 'Resident', 'password' => 'secret-pass-1',
    ])->assertSuccessful();

    $resident = User::where('email', 'nena@example.com')->sole();
    expect(Patient::sole()->resident_id)->toBe($resident->id);

    $this->actingAs($resident, 'sanctum')
        ->getJson(route('api.resident.visits'))
        ->assertOk()
        ->assertJsonCount(1, 'visits');
});

it('links earlier check-ups when a resident corrects their name or mobile', function () {
    $resident = linkResident(['mobile' => '09000000000']);
    $checkup = Patient::create(linkCheckup(['user_id' => linkGobiker()->id]));
    expect($checkup->resident_id)->toBeNull();

    $this->actingAs($resident, 'sanctum')
        ->putJson(route('api.resident.profile.update'), ['name' => 'Lola Nena', 'mobile' => '09181112222', 'barangay' => 'Hacienda'])
        ->assertOk();

    expect($checkup->fresh()->resident_id)->toBe($resident->id);
});

it('never moves a check-up that already belongs to a resident to another account', function () {
    $first = linkResident(['email' => 'first@example.com']);
    $checkup = Patient::create(linkCheckup(['user_id' => linkGobiker()->id]));

    linkResident(['email' => 'second@example.com']);

    expect($checkup->fresh()->resident_id)->toBe($first->id);
});

it('moves the check-up to the right resident when the GoBiker corrects the name', function () {
    $nena = linkResident();
    $pedro = linkResident(['name' => 'Pedro Reyes', 'email' => 'pedro@example.com']);
    $gobiker = linkGobiker();
    $checkup = Patient::create(linkCheckup(['user_id' => $gobiker->id]));
    expect($checkup->resident_id)->toBe($nena->id);

    Sanctum::actingAs($gobiker);
    $this->putJson(route('api.gobiker.patients.update', $checkup), linkCheckup(['name' => 'Pedro Reyes']))->assertOk();

    expect($checkup->fresh()->resident_id)->toBe($pedro->id);
});

it('keeps the check-up when the resident account is deleted', function () {
    $resident = linkResident();
    $checkup = Patient::create(linkCheckup(['user_id' => linkGobiker()->id]));

    $resident->delete();

    expect($checkup->fresh())->not->toBeNull()
        ->and($checkup->fresh()->resident_id)->toBeNull();
});

it('moves the admin follow-up decision when the GoBiker corrects the name', function () {
    $gobiker = linkGobiker();
    $checkup = Patient::create(linkCheckup(['name' => 'Lola Nina', 'user_id' => $gobiker->id]));
    PatientFollowUp::create([
        'person_key' => $checkup->personKey(), 'name' => 'Lola Nina', 'contact' => '09181112222',
        'decision' => 'stop', 'decided_at' => now(),
    ]);

    Sanctum::actingAs($gobiker);
    $this->putJson(route('api.gobiker.patients.update', $checkup), linkCheckup())
        ->assertOk()
        ->assertJsonPath('patient.follow_up.decision', 'stop');

    expect(PatientFollowUp::sole()->person_key)->toBe(Patient::makePersonKey('Lola Nena', '09181112222'));
});

it('keeps the follow-up on the old name while other check-ups still use it', function () {
    $gobiker = linkGobiker();
    $checkup = Patient::create(linkCheckup(['name' => 'Lola Nina', 'user_id' => $gobiker->id]));
    Patient::create(linkCheckup(['name' => 'Lola Nina', 'user_id' => $gobiker->id]));
    PatientFollowUp::create([
        'person_key' => $checkup->personKey(), 'name' => 'Lola Nina', 'contact' => '09181112222',
        'decision' => 'stop', 'decided_at' => now(),
    ]);

    Sanctum::actingAs($gobiker);
    $this->putJson(route('api.gobiker.patients.update', $checkup), linkCheckup())->assertOk();

    expect(PatientFollowUp::sole()->person_key)->toBe(Patient::makePersonKey('Lola Nina', '09181112222'));
});
