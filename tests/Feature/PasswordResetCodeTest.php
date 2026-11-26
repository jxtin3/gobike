<?php

use App\Mail\PasswordResetCode;
use App\Models\User;
use App\Support\Mfa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

/** Asks for a code by email and returns the one that was mailed. */
function mailedResetCode(string $email): string
{
    $code = null;
    Mail::assertSent(PasswordResetCode::class, function (PasswordResetCode $mail) use ($email, &$code) {
        $code = $mail->code;

        return $mail->hasTo($email);
    });

    return $code;
}

beforeEach(fn () => Mail::fake());

it('gives the same answer for an unknown email and sends nothing', function () {
    $this->postJson('/api/mobile/password/forgot', ['email' => 'nobody@example.com'])
        ->assertOk()
        ->assertJson(['message' => 'If that email has an account, we sent a code.']);

    Mail::assertNothingSent();
});

it('emails a 6-digit code to a known account', function () {
    User::factory()->create(['email' => 'maria@example.com']);

    $this->postJson('/api/mobile/password/forgot', ['email' => 'Maria@Example.com '])
        ->assertOk()
        ->assertJson(['message' => 'If that email has an account, we sent a code.']);

    expect(mailedResetCode('maria@example.com'))->toMatch('/^\d{6}$/');
});

it('resets the password with the code, signs out every phone, and does not sign in', function () {
    $user = User::factory()->create(['email' => 'maria@example.com', 'password' => 'old-password']);
    $user->createToken('phone');

    $this->postJson('/api/mobile/password/forgot', ['email' => 'maria@example.com'])->assertOk();
    $code = mailedResetCode('maria@example.com');

    $this->postJson('/api/mobile/password/reset', [
        'email' => 'maria@example.com',
        'code' => $code,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertOk()->assertJsonMissingPath('token');

    $user->refresh();
    expect(Hash::check('new-password', $user->password))->toBeTrue()
        ->and($user->tokens()->count())->toBe(0);

    // The code is used up.
    $this->postJson('/api/mobile/password/reset', [
        'email' => 'maria@example.com',
        'code' => $code,
        'password' => 'other-password',
        'password_confirmation' => 'other-password',
    ])->assertUnprocessable()->assertJsonValidationErrors(['code' => 'That code is wrong or has expired.']);
});

it('kills the code after 5 wrong tries', function () {
    User::factory()->create(['email' => 'maria@example.com']);
    $this->postJson('/api/mobile/password/forgot', ['email' => 'maria@example.com']);
    $code = mailedResetCode('maria@example.com');
    $wrong = $code === '000000' ? '111111' : '000000';

    $reset = fn (string $c) => $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.'.random_int(1, 250)])
        ->postJson('/api/mobile/password/reset', [
            'email' => 'maria@example.com', 'code' => $c,
            'password' => 'new-password', 'password_confirmation' => 'new-password',
        ]);

    for ($i = 0; $i < 5; $i++) {
        $reset($wrong)->assertUnprocessable();
    }

    $reset($code)->assertUnprocessable()->assertJsonValidationErrors('code');
});

it('lets a code expire after 15 minutes', function () {
    User::factory()->create(['email' => 'maria@example.com']);
    $this->postJson('/api/mobile/password/forgot', ['email' => 'maria@example.com']);
    $code = mailedResetCode('maria@example.com');

    $this->travel(16)->minutes();

    $this->postJson('/api/mobile/password/reset', [
        'email' => 'maria@example.com', 'code' => $code,
        'password' => 'new-password', 'password_confirmation' => 'new-password',
    ])->assertUnprocessable()->assertJsonValidationErrors('code');
});

it('sends a new code only after 60 seconds, and the new one replaces the old', function () {
    User::factory()->create(['email' => 'maria@example.com']);
    $this->postJson('/api/mobile/password/forgot', ['email' => 'maria@example.com']);
    $this->postJson('/api/mobile/password/forgot', ['email' => 'maria@example.com']);
    Mail::assertSentCount(1);
    $first = mailedResetCode('maria@example.com');

    $this->travel(61)->seconds();
    $this->postJson('/api/mobile/password/forgot', ['email' => 'maria@example.com']);
    Mail::assertSentCount(2);

    $codes = Mail::sent(PasswordResetCode::class)->map->code->all();
    $second = end($codes);

    if ($first !== $second) {
        $this->postJson('/api/mobile/password/reset', [
            'email' => 'maria@example.com', 'code' => $first,
            'password' => 'new-password', 'password_confirmation' => 'new-password',
        ])->assertUnprocessable();
    }

    $this->postJson('/api/mobile/password/reset', [
        'email' => 'maria@example.com', 'code' => $second,
        'password' => 'new-password', 'password_confirmation' => 'new-password',
    ])->assertOk();
});

it('still asks for the MFA code after a reset', function () {
    $user = User::factory()->create(['email' => 'maria@example.com', 'password' => 'old-password']);
    $secret = Mfa::startSetup($user);
    Mfa::enable($user, app(Google2FA::class)->getCurrentOtp($secret));

    $this->postJson('/api/mobile/password/forgot', ['email' => 'maria@example.com']);
    $this->postJson('/api/mobile/password/reset', [
        'email' => 'maria@example.com', 'code' => mailedResetCode('maria@example.com'),
        'password' => 'new-password', 'password_confirmation' => 'new-password',
    ])->assertOk();

    $this->postJson('/api/mobile/login', ['email' => 'maria@example.com', 'password' => 'new-password'])
        ->assertUnprocessable()
        ->assertJson(['mfa_required' => true]);
});

it('lets an admin reset their password on the website', function () {
    $admin = User::factory()->create(['email' => 'admin@example.com', 'is_admin' => true, 'password' => 'old-password-123']);
    $oldRemember = $admin->remember_token;

    $this->get('/login')->assertSee('Forgot password?');

    $this->post('/forgot-password', ['email' => 'admin@example.com'])
        ->assertRedirect(route('password.reset'));
    $code = mailedResetCode('admin@example.com');

    $this->get(route('password.reset'))->assertOk()->assertSee('admin@example.com');

    // Admin passwords follow the admin rules.
    $this->post('/reset-password', ['code' => $code, 'password' => 'short', 'password_confirmation' => 'short'])
        ->assertSessionHasErrors('password');

    $this->post('/reset-password', ['code' => $code, 'password' => 'Brand-new-pass-123', 'password_confirmation' => 'Brand-new-pass-123'])
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', 'Password changed. Sign in with your new password.');

    $admin->refresh();
    expect(Hash::check('Brand-new-pass-123', $admin->password))->toBeTrue()
        ->and($admin->remember_token)->not->toBe($oldRemember);
    $this->assertGuest();
});

it('gives the website the same answer for an unknown email', function () {
    $this->post('/forgot-password', ['email' => 'nobody@example.com'])
        ->assertRedirect(route('password.reset'))
        ->assertSessionHas('status', 'If that email has an account, we sent a code.');

    Mail::assertNothingSent();
});
