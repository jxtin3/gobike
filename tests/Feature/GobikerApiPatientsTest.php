<?php

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function checkupPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Lola Nena', 'address' => 'Purok 2', 'contact' => '09181112222', 'age' => 72,
        'sys' => 120, 'dia' => 78, 'pulse' => 70, 'resp' => 16, 'temp' => 36.5,
        'barangay' => 'Hacienda',
    ], $overrides);
}

beforeEach(function () {
    $this->gobiker = User::factory()->create(['role' => 'GoBiker', 'status' => 'Active']);
    Sanctum::actingAs($this->gobiker);
});

it('saves a check-up with blood sugar and no height or weight', function () {
    $response = $this->postJson(route('api.gobiker.patients.store'), checkupPayload(['blood_sugar' => 126]))
        ->assertCreated();

    expect($response->json('patient.blood_sugar'))->toBe(126)
        ->and($response->json('patient'))->not->toHaveKeys(['height', 'weight'])
        ->and(Patient::sole()->blood_sugar)->toBe(126);
});

it('does not need blood sugar, so older app versions keep working', function () {
    $this->postJson(route('api.gobiker.patients.store'), checkupPayload())
        ->assertCreated()
        ->assertJsonPath('patient.blood_sugar', null);
});

it('ignores height and weight sent by an older app version', function () {
    $this->postJson(route('api.gobiker.patients.store'), checkupPayload(['height' => 165, 'weight' => 60]))
        ->assertCreated();

    expect(Patient::sole()->height)->toBeNull();
});

it('rejects a blood sugar that cannot be real', function (mixed $value) {
    $this->postJson(route('api.gobiker.patients.store'), checkupPayload(['blood_sugar' => $value]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('blood_sugar');
})->with([10, 601, 'high', 99.5]);

it('updates the blood sugar of a check-up and lists it', function () {
    $patient = Patient::create(checkupPayload(['user_id' => $this->gobiker->id, 'recorded_at' => now()]));

    $this->putJson(route('api.gobiker.patients.update', $patient), checkupPayload(['blood_sugar' => 88]))
        ->assertOk()
        ->assertJsonPath('patient.blood_sugar', 88);

    $this->getJson(route('api.gobiker.patients.index'))
        ->assertOk()
        ->assertJsonPath('patients.0.blood_sugar', 88);
});
