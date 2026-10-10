<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function userManagementAdmin(): User
{
    return User::factory()->create(['is_admin' => true, 'role' => 'Admin']);
}

function userManagementEditPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'GoBiker Example',
        'email' => 'gobiker@example.com',
        'mobile' => '09123456789',
        'barangay' => 'Poblacion',
        'role' => 'GoBiker',
    ], $overrides);
}

it('allows an admin to update a user mobile number and barangay', function () {
    $admin = userManagementAdmin();
    $user = User::factory()->create([
        'role' => 'GoBiker',
        'mobile' => '09987654321',
        'barangay' => 'Angarian',
    ]);

    $this->actingAs($admin)
        ->put(route('admin.operations.users.update', $user), userManagementEditPayload())
        ->assertRedirect(route('admin.operations.users.index'))
        ->assertSessionHas('success', 'User successfully updated.');

    expect($user->fresh()->mobile)->toBe('09123456789')
        ->and($user->fresh()->barangay)->toBe('Poblacion');
});

it('rejects invalid or duplicate admin-edited mobile numbers', function (string $mobile, bool $duplicate) {
    $admin = userManagementAdmin();
    $user = User::factory()->create(['role' => 'GoBiker']);
    if ($duplicate) {
        User::factory()->create(['mobile' => '09123456789']);
    }

    $this->actingAs($admin)
        ->put(route('admin.operations.users.update', $user), userManagementEditPayload(['mobile' => $mobile]))
        ->assertSessionHasErrors('mobile');
})->with([
    'invalid format' => ['08123456789', false],
    'duplicate number' => ['09123456789', true],
]);

it('allows an admin to reset a GoBiker password without exposing its existing value', function () {
    $admin = userManagementAdmin();
    $user = User::factory()->create([
        'role' => 'GoBiker',
        'password' => 'old-password',
    ]);

    $this->actingAs($admin)
        ->put(route('admin.operations.users.update', $user), userManagementEditPayload([
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]))
        ->assertRedirect(route('admin.operations.users.index'));

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue()
        ->and(Hash::check('old-password', $user->fresh()->password))->toBeFalse();
});

it('keeps a GoBiker password unchanged when the admin leaves the password blank', function () {
    $admin = userManagementAdmin();
    $user = User::factory()->create([
        'role' => 'GoBiker',
        'password' => 'old-password',
    ]);

    $this->actingAs($admin)
        ->put(route('admin.operations.users.update', $user), userManagementEditPayload())
        ->assertRedirect(route('admin.operations.users.index'));

    expect(Hash::check('old-password', $user->fresh()->password))->toBeTrue();
});
