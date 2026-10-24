<?php

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function resident(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'name' => 'Juan Dela Cruz',
        'role' => 'User',
        'status' => 'Active',
        'mobile' => '09123456789',
        'barangay' => 'Poblacion',
        'password' => 'old-password',
    ], $attributes));
}

function checkupFor(string $name, string $contact, array $attributes = []): Patient
{
    $attributes['user_id'] ??= User::factory()->create(['role' => 'GoBiker'])->id;

    return Patient::create(array_merge([
        'name' => $name, 'contact' => $contact, 'address' => 'Purok 3', 'age' => 45,
        'sys' => 120, 'dia' => 80, 'pulse' => 72, 'resp' => 16, 'temp' => 36.6,
        'barangay' => 'Poblacion', 'recorded_at' => now(),
    ], $attributes));
}

// ---------------------------------------------------------------- profile

it('updates the resident profile and returns the user like login does', function () {
    $user = resident();

    $this->actingAs($user, 'sanctum')
        ->putJson(route('api.resident.profile.update'), ['name' => '  Juana Dela Cruz ', 'mobile' => '09998887777', 'barangay' => 'Hacienda'])
        ->assertOk()
        ->assertJsonPath('user.name', 'Juana Dela Cruz')
        ->assertJsonPath('user.mobile', '09998887777')
        ->assertJsonPath('user.barangay', 'Hacienda')
        ->assertJsonPath('user.role', 'User');

    expect($user->fresh()->name)->toBe('Juana Dela Cruz');
});

it('lets family members share one mobile number', function () {
    resident(['email' => 'mother@example.com']);
    $child = resident(['email' => 'child@example.com', 'mobile' => '09111111111']);

    $this->actingAs($child, 'sanctum')
        ->putJson(route('api.resident.profile.update'), ['name' => 'Child', 'mobile' => '09123456789', 'barangay' => 'Poblacion'])
        ->assertOk();
});

it('rejects an invalid resident profile', function (array $payload, string $field) {
    $this->actingAs(resident(), 'sanctum')
        ->putJson(route('api.resident.profile.update'), array_merge(['name' => 'Juan', 'mobile' => '09123456789', 'barangay' => 'Poblacion'], $payload))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'bad mobile' => [['mobile' => '12345'], 'mobile'],
    'unknown barangay' => [['barangay' => 'Atlantis'], 'barangay'],
    'empty name' => [['name' => ''], 'name'],
]);

it('keeps GoBikers and inactive residents out of the resident endpoints', function (array $attributes) {
    $this->actingAs(resident($attributes), 'sanctum')
        ->getJson(route('api.resident.visits'))
        ->assertForbidden();
})->with([
    'GoBiker' => [['role' => 'GoBiker']],
    'inactive' => [['status' => 'Inactive']],
]);

// ---------------------------------------------------------------- visits

it('lists only the check-ups of the signed-in resident, newest first, with remarks', function () {
    $gobiker = User::factory()->create(['name' => 'Maria Santos', 'role' => 'GoBiker']);
    $old = checkupFor('juan dela cruz ', '09123456789', ['recorded_at' => now()->subMonth(), 'user_id' => $gobiker->id]);
    $new = checkupFor('Juan Dela Cruz', '09123456789', ['remarks' => 'Less salt, recheck in a week.', 'user_id' => $gobiker->id]);
    checkupFor('Juan Dela Cruz', '09000000000'); // same name, another person
    checkupFor('Pedro Reyes', '09123456789');   // same phone, another person

    $response = $this->actingAs(resident(), 'sanctum')
        ->getJson(route('api.resident.visits'))
        ->assertOk()
        ->assertJsonCount(2, 'visits');

    expect($response->json('visits.*.id'))->toBe([$new->id, $old->id])
        ->and($response->json('visits.0.remarks'))->toBe('Less salt, recheck in a week.')
        ->and($response->json('visits.0.gobiker_name'))->toBe('Maria Santos');
});

it('shows no check-ups to a resident without a mobile number', function () {
    checkupFor('Juan Dela Cruz', '');

    $this->actingAs(resident(['mobile' => null]), 'sanctum')
        ->getJson(route('api.resident.visits'))
        ->assertOk()
        ->assertJsonCount(0, 'visits');
});

// ---------------------------------------------------------------- password

it('changes the password and signs out the other phones only', function () {
    $user = resident();
    $user->createToken('other-phone');
    $current = $user->createToken('this-phone')->plainTextToken;

    $this->withToken($current)
        ->putJson(route('api.mobile.password'), [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertOk();

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue()
        ->and($user->tokens()->pluck('name')->all())->toBe(['this-phone']);
});

it('refuses a password change with the wrong current password or no confirmation', function (array $payload, string $field) {
    $user = resident();

    $this->actingAs($user, 'sanctum')
        ->putJson(route('api.mobile.password'), array_merge([
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ], $payload))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);

    expect(Hash::check('old-password', $user->fresh()->password))->toBeTrue();
})->with([
    'wrong current' => [['current_password' => 'nope'], 'current_password'],
    'no confirmation' => [['password_confirmation' => 'other'], 'password'],
    'too short' => [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
]);

it('requires sign-in to change the password', function () {
    $this->putJson(route('api.mobile.password'), [])->assertUnauthorized();
});
