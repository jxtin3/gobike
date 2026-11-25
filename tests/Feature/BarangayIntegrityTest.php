<?php

use App\Models\Barangay;
use App\Models\User;
use App\Support\Barangays;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('seeds every barangay of Bugallon', function () {
    $bugallon = Barangay::where('municipality', 'Bugallon')->orderBy('sort_order')->pluck('name')->all();

    expect($bugallon)->toBe(Barangays::ALL);
});

it('refuses a barangay that is not on the list at the database level', function () {
    User::factory()->create(['barangay' => 'Atlantis']);
})->throws(QueryException::class);

it('rejects an unknown barangay when signing up in the app', function () {
    $this->postJson('/api/mobile/register', [
        'name' => 'Lola Nena', 'email' => 'nena@example.com', 'mobile' => '09181112222',
        'barangay' => 'Atlantis', 'role' => 'Resident', 'password' => 'secret-pass-1',
    ])->assertUnprocessable()->assertJsonValidationErrors('barangay');
});

it('accepts a barangay from another Pangasinan town when signing up', function () {
    // Seed one other town the way the PhilippineLocationsSeeder does: city →
    // municipality → barangay, with the hierarchy ids on the barangay row.
    $province = \App\Models\Province::firstOrCreate(['name' => 'Pangasinan']);
    $city = \App\Models\City::firstOrCreate(['province_id' => $province->id, 'name' => 'Calasiao']);
    $municipality = \App\Models\Municipality::firstOrCreate(['city_id' => $city->id, 'name' => 'Calasiao']);
    Barangay::firstOrCreate(
        ['name' => 'Talibaew (Calasiao)'],
        ['municipality' => 'Calasiao', 'sort_order' => 0, 'province_id' => $province->id, 'city_id' => $city->id, 'municipality_id' => $municipality->id],
    );

    $this->postJson('/api/mobile/register', [
        'name' => 'Ana Reyes', 'email' => 'ana@example.com', 'mobile' => '09183334444',
        'barangay' => 'Talibaew (Calasiao)', 'role' => 'Resident', 'password' => 'secret-pass-1',
    ])->assertCreated();

    $user = User::where('email', 'ana@example.com')->first();
    expect($user->barangay)->toBe('Talibaew (Calasiao)')
        ->and($user->municipality_id)->toBe($municipality->id)
        ->and($user->province_id)->toBe($province->id);
});

it('rejects an unknown barangay on a check-up', function () {
    Sanctum::actingAs(User::factory()->create(['role' => 'GoBiker', 'status' => 'Active']));

    $this->postJson(route('api.gobiker.patients.store'), [
        'name' => 'Lola Nena', 'address' => 'Purok 2', 'contact' => '09181112222', 'age' => 72,
        'sys' => 120, 'dia' => 78, 'pulse' => 70, 'resp' => 16, 'temp' => 36.5,
        'barangay' => 'Atlantis',
    ])->assertUnprocessable()->assertJsonValidationErrors('barangay');
});

it('rejects an unknown barangay on an SOS', function () {
    Sanctum::actingAs(User::factory()->create(['role' => 'User', 'status' => 'Active']));

    $this->postJson(route('api.resident.sos.store'), [
        'municipality' => 'Bugallon', 'barangay' => 'Atlantis', 'landmark' => 'Near the chapel',
    ])->assertUnprocessable()->assertJsonValidationErrors('barangay');
});

it('rejects an unknown barangay when an admin adds a user', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]))
        ->post(route('admin.operations.users.store'), [
            'name' => 'New GoBiker', 'email' => 'new@example.com', 'barangay' => 'Atlantis', 'role' => 'GoBiker',
            'password' => 'long-enough-pass-9', 'password_confirmation' => 'long-enough-pass-9',
        ])
        ->assertSessionHasErrors('barangay');

    $this->assertDatabaseMissing('users', ['email' => 'new@example.com']);
});
