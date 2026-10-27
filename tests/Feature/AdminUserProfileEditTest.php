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
        ->assertRedirect(route('admin.operations.users.show', $user))
        ->assertSessionHas('success', 'User updated.');

    expect($user->fresh()->mobile)->toBe('09123456789')
        ->and($user->fresh()->barangay)->toBe('Poblacion');
});

it('offers the barangay list in the user form with the current one selected', function () {
    $admin = userManagementAdmin();
    $user = User::factory()->create(['role' => 'GoBiker', 'barangay' => 'Angarian']);

    $this->actingAs($admin)
        ->get(route('admin.operations.users.edit', $user))
        ->assertOk()
        ->assertSee('<select class="input " id="barangay" name="barangay"', false)
        ->assertSee('<option value="Angarian" selected>Angarian</option>', false)
        ->assertSee('<option value="Bañaga" >Bañaga</option>', false);

    $this->actingAs($admin)
        ->get(route('admin.operations.users.create'))
        ->assertOk()
        ->assertSee('<option value="" selected>Not set</option>', false);
});

it('clears the barangay when the admin picks "Not set"', function () {
    $admin = userManagementAdmin();
    $user = User::factory()->create(['role' => 'GoBiker', 'barangay' => 'Angarian']);

    $this->actingAs($admin)
        ->put(route('admin.operations.users.update', $user), userManagementEditPayload(['barangay' => '']))
        ->assertRedirect(route('admin.operations.users.show', $user));

    expect($user->fresh()->barangay)->toBeNull();
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
            'password' => 'new-password-2026',
            'password_confirmation' => 'new-password-2026',
        ]))
        ->assertRedirect(route('admin.operations.users.show', $user));

    expect(Hash::check('new-password-2026', $user->fresh()->password))->toBeTrue()
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
        ->assertRedirect(route('admin.operations.users.show', $user));

    expect(Hash::check('old-password', $user->fresh()->password))->toBeTrue();
});
