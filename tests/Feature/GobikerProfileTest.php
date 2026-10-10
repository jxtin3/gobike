<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function validGobikerProfilePayload(array $overrides = []): array
{
    return array_merge([
        'mobile' => '09123456789',
        'barangay' => 'Poblacion',
    ], $overrides);
}

function goBikerUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'role' => 'GoBiker',
        'mobile' => '09987654321',
        'barangay' => 'Angarian',
        'password' => 'old-password',
    ], $attributes));
}

it('updates the authenticated GoBiker profile', function () {
    $user = goBikerUser();

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/gobiker/profile', validGobikerProfilePayload())
        ->assertOk()
        ->assertExactJson([
            'message' => 'Profile updated successfully.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile' => '09123456789',
                'barangay' => 'Poblacion',
                'role' => 'GoBiker',
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'mobile' => '09123456789',
        'barangay' => 'Poblacion',
    ]);

    expect(Hash::check('old-password', $user->fresh()->password))->toBeTrue();
});

it('rejects an invalid mobile number', function (string $mobile) {
    $user = goBikerUser();

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/gobiker/profile', validGobikerProfilePayload(['mobile' => $mobile]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('mobile');
})->with([
    'wrong prefix' => '08123456789',
    'too short' => '0912345678',
    'non-digit' => '0912345678x',
]);

it('rejects an unsupported barangay', function () {
    $user = goBikerUser();

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/gobiker/profile', validGobikerProfilePayload(['barangay' => 'Not a Barangay']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('barangay');
});

it('rejects a mobile number already used by another user', function () {
    $user = goBikerUser();
    User::factory()->create(['mobile' => '09123456789']);

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/gobiker/profile', validGobikerProfilePayload())
        ->assertUnprocessable()
        ->assertJsonValidationErrors('mobile');
});

it('allows a GoBiker to change their password with the correct current password', function () {
    $user = goBikerUser();

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/gobiker/profile', validGobikerProfilePayload([
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]))
        ->assertOk();

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
});

it('rejects a password change with the wrong current password', function () {
    $user = goBikerUser();

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/gobiker/profile', validGobikerProfilePayload([
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('current_password');

    expect(Hash::check('old-password', $user->fresh()->password))->toBeTrue();
});

it('requires password confirmation when changing the password', function () {
    $user = goBikerUser();

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/gobiker/profile', validGobikerProfilePayload([
            'current_password' => 'old-password',
            'password' => 'new-password',
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('password_confirmation');
});

it('forbids profile updates by non-GoBiker users', function () {
    $user = User::factory()->create(['role' => 'User']);

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/gobiker/profile', validGobikerProfilePayload())
        ->assertForbidden();
});

it('requires authentication to update a GoBiker profile', function () {
    $this->putJson('/api/gobiker/profile', validGobikerProfilePayload())
        ->assertUnauthorized();
});
