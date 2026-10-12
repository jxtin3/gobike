<?php

use App\Models\User;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Hash;

function fakeGoogle(array|false $payload): void
{
    config(['services.google.client_id' => 'test-client-id']);

    $mock = Mockery::mock(GoogleClient::class);
    $mock->shouldReceive('verifyIdToken')->andReturn($payload);

    app()->bind(GoogleClient::class, fn () => $mock);
}

function victimGooglePayload(): array
{
    return [
        'email' => 'victim@gmail.com',
        'sub' => 'google-sub-123',
        'email_verified' => true,
        'name' => 'Victim',
    ];
}

it('cuts off a password set by someone who never proved they own the email', function () {
    $attacker = User::factory()->create([
        'email' => 'victim@gmail.com',
        'password' => bcrypt('attacker-password'),
        'email_verified_at' => null,
        'google_id' => null,
        'role' => 'User',
        'status' => 'Active',
    ]);
    $attacker->createToken('attacker-device');

    fakeGoogle(victimGooglePayload());

    $this->postJson('/api/mobile/google', ['id_token' => 'any'])->assertOk();

    $account = $attacker->fresh();

    expect($account->google_id)->toBe('google-sub-123');
    expect(Hash::check('attacker-password', $account->password))->toBeFalse();
    expect($account->tokens()->where('name', 'attacker-device')->count())->toBe(0);

    $this->postJson('/api/mobile/login', [
        'email' => 'victim@gmail.com',
        'password' => 'attacker-password',
    ])->assertUnprocessable();
});

it('keeps the password of an account whose email was already verified', function () {
    $user = User::factory()->create([
        'email' => 'victim@gmail.com',
        'password' => bcrypt('my-own-password'),
        'email_verified_at' => now(),
        'google_id' => null,
        'role' => 'User',
        'status' => 'Active',
    ]);

    fakeGoogle(victimGooglePayload());

    $this->postJson('/api/mobile/google', ['id_token' => 'any'])->assertOk();

    expect(Hash::check('my-own-password', $user->fresh()->password))->toBeTrue();
});

it('never touches the password of an admin account', function () {
    $admin = User::factory()->create([
        'email' => 'victim@gmail.com',
        'password' => bcrypt('admin-password-123'),
        'email_verified_at' => null,
        'google_id' => null,
        'is_admin' => true,
        'status' => 'Active',
    ]);

    fakeGoogle(victimGooglePayload());

    $this->postJson('/api/mobile/google', ['id_token' => 'any'])->assertOk();

    expect(Hash::check('admin-password-123', $admin->fresh()->password))->toBeTrue();
});

it('still creates a new account for a first-time Google user', function () {
    fakeGoogle(victimGooglePayload());

    $this->postJson('/api/mobile/google', ['id_token' => 'any'])
        ->assertOk()
        ->assertJsonStructure(['token', 'user' => ['id', 'email']]);

    expect(User::where('email', 'victim@gmail.com')->value('google_id'))->toBe('google-sub-123');
});