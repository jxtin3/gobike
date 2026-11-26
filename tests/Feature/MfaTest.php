<?php

use App\Models\User;
use App\Support\Mfa;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

/** @return list<string> the plain backup codes */
function turnOnMfa(User $user): array
{
    $secret = Mfa::startSetup($user);

    return Mfa::enable($user, app(Google2FA::class)->getCurrentOtp($secret));
}

function otpFor(User $user): string
{
    return app(Google2FA::class)->getCurrentOtp($user->fresh()->mfa_secret);
}

function mfaAdmin(array $attributes = []): User
{
    $admin = User::factory()->create(array_merge([
        'email' => 'admin@example.com',
        'password' => Hash::make('password123'),
        'is_admin' => true,
    ], $attributes));
    turnOnMfa($admin);

    return $admin->fresh();
}

it('sends an admin without two-step login to setup and blocks the panel until it is done', function () {
    $admin = User::factory()->create(['email' => 'admin@example.com', 'password' => Hash::make('password123'), 'is_admin' => true]);

    $this->post('/login', ['email' => 'admin@example.com', 'password' => 'password123'])
        ->assertRedirect(route('admin.mfa.setup'));

    $this->get('/admin')->assertRedirect(route('admin.mfa.setup'));
    $this->get(route('admin.operations.users.index'))->assertRedirect(route('admin.mfa.setup'));
    $this->get(route('admin.mfa.setup'))->assertOk()->assertSee('Set up two-step login')->assertSee('Sign out');

    $this->post(route('admin.mfa.verify'), ['code' => '000000'])->assertSessionHasErrors('code');

    $this->post(route('admin.mfa.verify'), ['code' => otpFor($admin)])
        ->assertRedirect(route('admin.mfa.codes'));

    $codes = $this->get(route('admin.mfa.codes'))->assertOk()->viewData('backupCodes');
    expect($codes)->toHaveCount(10)
        ->and($codes[0])->toMatch('/^[a-z2-9]{4}-[a-z2-9]{4}$/')
        ->and($admin->fresh()->hasMfaEnabled())->toBeTrue();

    // Shown once only.
    $this->get(route('admin.mfa.codes'))->assertRedirect(route('admin.profile.show'));
    $this->get('/admin')->assertOk();
});

it('keeps the same secret when the setup page is opened again', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $this->get(route('admin.mfa.setup'))->assertOk();
    $first = $admin->fresh()->mfa_secret;
    $this->get(route('admin.mfa.setup'))->assertOk();

    expect($first)->not->toBeEmpty()->and($admin->fresh()->mfa_secret)->toBe($first);
});

it('asks an admin with two-step login for the code before signing in', function () {
    $admin = mfaAdmin();

    $this->post('/login', ['email' => 'admin@example.com', 'password' => 'password123', 'remember' => '1'])
        ->assertRedirect(route('mfa.verify'));
    $this->assertGuest();
    $this->get(route('mfa.verify'))->assertOk()->assertSee('Use a backup code');

    $this->post(route('mfa.verify.submit'), ['code' => '000000'])->assertSessionHasErrors('code');
    $this->assertGuest();

    $this->post(route('mfa.verify.submit'), ['code' => otpFor($admin)])
        ->assertRedirect(route('admin.dashboard'))
        ->assertCookie(Auth::guard('web')->getRecallerName());
    $this->assertAuthenticatedAs($admin);
    $this->get('/admin')->assertOk()->assertSee('adm-dashboard-entrance', false);
});

it('does not set the remember cookie when remember me was not ticked', function () {
    $admin = mfaAdmin();

    $this->post('/login', ['email' => 'admin@example.com', 'password' => 'password123']);
    $this->post(route('mfa.verify.submit'), ['code' => otpFor($admin)])
        ->assertCookieMissing(Auth::guard('web')->getRecallerName());
});

it('accepts each backup code once', function () {
    $admin = User::factory()->create(['email' => 'admin@example.com', 'password' => Hash::make('password123'), 'is_admin' => true]);
    $codes = turnOnMfa($admin);

    $this->post('/login', ['email' => 'admin@example.com', 'password' => 'password123']);
    $this->post(route('mfa.verify.submit'), ['code' => strtoupper($codes[0])])->assertRedirect(route('admin.dashboard'));
    expect(Mfa::backupCodesLeft($admin->fresh()))->toBe(9);

    $this->post('/logout');
    $this->post('/login', ['email' => 'admin@example.com', 'password' => 'password123']);
    $this->post(route('mfa.verify.submit'), ['code' => $codes[0]])->assertSessionHasErrors('code');
    $this->assertGuest();
});

it('locks the code step after five wrong codes', function () {
    $admin = mfaAdmin();

    $this->post('/login', ['email' => 'admin@example.com', 'password' => 'password123']);
    foreach (range(1, 5) as $_) {
        $this->post(route('mfa.verify.submit'), ['code' => '000000']);
    }

    $this->post(route('mfa.verify.submit'), ['code' => otpFor($admin)])
        ->assertSessionHasErrors(['code' => 'Too many wrong codes. Try again in 300 seconds.']);
    $this->assertGuest();
});

it('gives an admin new backup codes only with a current code, and has no way to turn it off', function () {
    $admin = mfaAdmin();
    $this->actingAs($admin)->withSession(['mfa_verified' => true]);

    $this->get(route('admin.profile.edit'))->assertOk()->assertSee('10 of 10 backup codes left')->assertDontSee('Disable');

    $this->post(route('admin.mfa.codes.regenerate'), ['mfa_code' => '000000'])->assertSessionHasErrors('mfa_code');
    $this->post(route('admin.mfa.codes.regenerate'), ['mfa_code' => otpFor($admin)])->assertRedirect(route('admin.mfa.codes'));

    $this->post('/admin/mfa/disable')->assertNotFound();
    expect($admin->fresh()->hasMfaEnabled())->toBeTrue();
});

it('lets an admin turn off two-step login for a resident who lost their phone', function () {
    $admin = mfaAdmin();
    $resident = User::factory()->create(['role' => 'Resident', 'status' => 'Active']);
    turnOnMfa($resident);
    $resident->createToken('phone');

    $this->actingAs($admin)->withSession(['mfa_verified' => true]);
    $this->get(route('admin.operations.users.show', $resident))->assertOk()->assertSee('Two-step login');

    $this->post(route('admin.operations.users.mfa-reset', $resident))->assertSessionHas('success');

    expect($resident->fresh()->hasMfaEnabled())->toBeFalse()
        ->and($resident->tokens()->count())->toBe(0);
});

it('does not let the user page turn off an admin\'s two-step login', function () {
    $admin = mfaAdmin();
    $other = mfaAdmin(['email' => 'other@example.com']);

    $this->actingAs($admin)->withSession(['mfa_verified' => true])
        ->post(route('admin.operations.users.mfa-reset', $other))->assertSessionHas('error');

    expect($other->fresh()->hasMfaEnabled())->toBeTrue();
});

it('resets an admin\'s two-step login from the command line', function () {
    $admin = mfaAdmin();

    $this->artisan('admin:mfa-reset', ['email' => 'ADMIN@example.com'])
        ->expectsConfirmation('Turn off two-step login for '.$admin->name.' (admin@example.com)? Do this only if you are sure it is really them.', 'yes')
        ->assertSuccessful();

    expect($admin->fresh()->hasMfaEnabled())->toBeFalse();

    $this->artisan('admin:mfa-reset', ['email' => 'nobody@example.com'])->assertFailed();
});

it('asks for the code at mobile sign-in and gives plain backup codes', function () {
    $user = User::factory()->create(['email' => 'maria@example.com', 'password' => 'secret-pass', 'role' => 'Resident', 'status' => 'Active']);
    $token = $user->createToken('phone')->plainTextToken;

    $setup = $this->withToken($token)->postJson('/api/mobile/mfa/setup')->assertOk();
    expect($setup->json('otpauth_url'))->toStartWith('otpauth://totp/');

    $codes = $this->withToken($token)->postJson('/api/mobile/mfa/verify', ['code' => otpFor($user)])
        ->assertOk()->json('backup_codes');
    expect($codes)->toHaveCount(10)->and($codes[0])->toMatch('/^[a-z2-9]{4}-[a-z2-9]{4}$/');

    $this->app['auth']->forgetGuards();

    $this->postJson('/api/mobile/login', ['email' => 'maria@example.com', 'password' => 'secret-pass'])
        ->assertUnprocessable()->assertJson(['mfa_required' => true]);
    $this->postJson('/api/mobile/login', ['email' => 'maria@example.com', 'password' => 'secret-pass', 'mfa_code' => '000000'])
        ->assertUnprocessable()->assertJsonValidationErrors('mfa_code');
    $this->postJson('/api/mobile/login', ['email' => 'maria@example.com', 'password' => 'secret-pass', 'mfa_code' => $codes[0]])
        ->assertOk()->assertJsonStructure(['token']);
});
