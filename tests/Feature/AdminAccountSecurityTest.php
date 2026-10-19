<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('cannot make a user an admin through mass assignment', function () {
    $user = User::create([
        'name' => 'Sneaky',
        'email' => 'sneaky@example.com',
        'password' => 'a-long-password-123',
        'is_admin' => true,
    ]);

    expect($user->fresh()->is_admin)->toBeFalse();
});

it('creates an admin who can log in with the command', function () {
    $this->artisan('admin:create', ['--name' => 'Real Admin', '--email' => 'real@example.com'])
        ->expectsQuestion('Password (at least 12 characters)', 'Str0ng-password-123')
        ->expectsQuestion('Confirm password', 'Str0ng-password-123')
        ->assertSuccessful();

    $admin = User::where('email', 'real@example.com')->firstOrFail();

    expect($admin->is_admin)->toBeTrue();
    expect($admin->role)->toBe('Admin');
    expect(Hash::check('Str0ng-password-123', $admin->password))->toBeTrue();

    $this->post('/login', ['email' => 'real@example.com', 'password' => 'Str0ng-password-123'])
        ->assertRedirect();
    $this->assertAuthenticated();
});

it('refuses a weak admin password', function () {
    $this->artisan('admin:create', ['--name' => 'Weak Admin', '--email' => 'weak@example.com'])
        ->expectsQuestion('Password (at least 12 characters)', 'short')
        ->expectsQuestion('Confirm password', 'short')
        ->assertFailed();

    expect(User::where('email', 'weak@example.com')->exists())->toBeFalse();
});

it('refuses an email that is already used', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->artisan('admin:create', ['--name' => 'Copy', '--email' => 'taken@example.com'])
        ->expectsQuestion('Password (at least 12 characters)', 'Str0ng-password-123')
        ->expectsQuestion('Confirm password', 'Str0ng-password-123')
        ->assertFailed();
});
