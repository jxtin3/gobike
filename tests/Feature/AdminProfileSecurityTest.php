<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

function makeProfileAdmin(): User
{
    return User::factory()->create([
        'is_admin' => true,
        'password' => bcrypt('old-password-123'),
    ]);
}

it('requires the current password to change the password', function () {
    $admin = makeProfileAdmin();

    $this->actingAs($admin)->put(route('admin.profile.update'), [
        'name' => $admin->name,
        'email' => $admin->email,
        'password' => 'new-password-1234',
        'password_confirmation' => 'new-password-1234',
    ])->assertSessionHasErrors('current_password');

    expect(Hash::check('old-password-123', $admin->fresh()->password))->toBeTrue();
});

it('requires the current password to change the email', function () {
    $admin = makeProfileAdmin();
    $oldEmail = $admin->email;

    $this->actingAs($admin)->put(route('admin.profile.update'), [
        'name' => $admin->name,
        'email' => 'attacker@example.com',
    ])->assertSessionHasErrors('current_password');

    expect($admin->fresh()->email)->toBe($oldEmail);
});

it('rejects a wrong current password', function () {
    $admin = makeProfileAdmin();

    $this->actingAs($admin)->put(route('admin.profile.update'), [
        'name' => $admin->name,
        'email' => $admin->email,
        'current_password' => 'not-my-password',
        'password' => 'new-password-1234',
        'password_confirmation' => 'new-password-1234',
    ])->assertSessionHasErrors('current_password');

    expect(Hash::check('old-password-123', $admin->fresh()->password))->toBeTrue();
});

it('changes the password when the current password is correct', function () {
    $admin = makeProfileAdmin();

    $this->actingAs($admin)->put(route('admin.profile.update'), [
        'name' => $admin->name,
        'email' => $admin->email,
        'current_password' => 'old-password-123',
        'password' => 'new-password-1234',
        'password_confirmation' => 'new-password-1234',
    ])->assertRedirect(route('admin.profile.show'));

    expect(Hash::check('new-password-1234', $admin->fresh()->password))->toBeTrue();
});

it('lets the admin change only their name without the current password', function () {
    $admin = makeProfileAdmin();

    $this->actingAs($admin)->put(route('admin.profile.update'), [
        'name' => 'New Display Name',
        'email' => $admin->email,
    ])->assertRedirect(route('admin.profile.show'));

    expect($admin->fresh()->name)->toBe('New Display Name');
});