<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

function makeActiveGoBiker(array $overrides = []): User
{
    return User::factory()->create(array_merge([
        'role' => 'GoBiker',
        'status' => 'Active',
        'mobile' => '09171234567',
        'barangay' => 'Poblacion',
        'password' => bcrypt('old-password-1'),
    ], $overrides));
}

it('gives mobile tokens an expiry by default', function () {
    expect(config('sanctum.expiration'))->not->toBeNull();
});

it('rejects an expired mobile token', function () {
    config(['sanctum.expiration' => 60]);
    $user = makeActiveGoBiker();
    $token = $user->createToken('go-biker-mobile');
    $token->accessToken->forceFill(['created_at' => now()->subMinutes(61)])->save();

    $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->getJson('/api/mobile/me')
        ->assertUnauthorized();
});

it('blocks an inactive GoBiker who still has a valid token', function () {
    Sanctum::actingAs(makeActiveGoBiker(['status' => 'Inactive']));

    $this->getJson('/api/gobiker/active')->assertForbidden();
});

it('logs other devices out when the password changes', function () {
    $user = makeActiveGoBiker();
    $user->createToken('other-phone');
    $current = $user->createToken('this-phone');

    $this->withHeader('Authorization', 'Bearer '.$current->plainTextToken)
        ->putJson('/api/gobiker/profile', [
            'mobile' => '09171234567',
            'barangay' => 'Poblacion',
            'current_password' => 'old-password-1',
            'password' => 'new-password-12',
            'password_confirmation' => 'new-password-12',
        ])
        ->assertOk();

    expect($user->tokens()->count())->toBe(1);
    expect($user->tokens()->first()->id)->toBe($current->accessToken->id);
});

it('rate limits the logged-in mobile API per user', function () {
    Sanctum::actingAs(makeActiveGoBiker());

    for ($i = 0; $i < 120; $i++) {
        $this->getJson('/api/gobiker/active');
    }

    $this->getJson('/api/gobiker/active')->assertStatus(429);
});