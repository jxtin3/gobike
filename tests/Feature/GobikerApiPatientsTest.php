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

it('saves and lists the GoBiker remarks for the resident', function () {
    $this->postJson(route('api.gobiker.patients.store'), checkupPayload(['remarks' => 'Drink more water.']))
        ->assertCreated()
        ->assertJsonPath('patient.remarks', 'Drink more water.');

    $this->getJson(route('api.gobiker.patients.index'))
        ->assertJsonPath('patients.0.remarks', 'Drink more water.');
});

it('rejects remarks that are too long', function () {
    $this->postJson(route('api.gobiker.patients.store'), checkupPayload(['remarks' => str_repeat('a', 1001)]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('remarks');
});

it('records that the person agreed to the check-up', function () {
    $this->postJson(route('api.gobiker.patients.store'), checkupPayload(['consent' => true]))
        ->assertCreated()
        ->assertJsonPath('patient.consent_given', true);

    expect(Patient::sole()->consent_given)->toBeTrue();
});

it('still saves a check-up from an older app that does not ask for consent', function () {
    $this->postJson(route('api.gobiker.patients.store'), checkupPayload())
        ->assertCreated()
        ->assertJsonPath('patient.consent_given', null);

    expect(Patient::sole()->consent_given)->toBeNull();
});

it('refuses a check-up the person did not agree to', function () {
    $this->postJson(route('api.gobiker.patients.store'), checkupPayload(['consent' => false]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('consent');

    expect(Patient::count())->toBe(0);
});

it('keeps the consent when a check-up is corrected later', function () {
    $id = $this->postJson(route('api.gobiker.patients.store'), checkupPayload(['consent' => true]))->json('patient.id');

    $this->putJson(route('api.gobiker.patients.update', $id), checkupPayload(['sys' => 130]))->assertOk();

    expect(Patient::find($id)->consent_given)->toBeTrue();
});

it('shows the consent on the admin check-up page', function () {
    $id = $this->postJson(route('api.gobiker.patients.store'), checkupPayload(['consent' => true]))->json('patient.id');
    $older = $this->postJson(route('api.gobiker.patients.store'), checkupPayload(['name' => 'Lolo Ben']))->json('patient.id');

    $this->actingAs(User::factory()->create(['is_admin' => true]), 'web');
    $this->get(route('admin.operations.patients.show', $id))->assertOk()->assertSee('Given');
    $this->get(route('admin.operations.patients.show', $older))->assertOk()->assertSee('Not recorded (older app version)');
});
