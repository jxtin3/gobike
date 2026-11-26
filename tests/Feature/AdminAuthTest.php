<?php

use App\Models\User;
use App\Support\Mfa;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

it('shows the admin login page', function () {
    $response = $this->get('/login');

    $response->assertStatus(200)
        ->assertSee('Go Bike Admin');
});

it('allows an admin user to access the dashboard', function () {
    $user = User::factory()->create([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'password' => Hash::make('password123'),
        'is_admin' => true,
    ]);

    $secret = Mfa::startSetup($user);
    Mfa::enable($user, app(Google2FA::class)->getCurrentOtp($secret));

    $this->post('/login', [
        'email' => 'admin@example.com',
        'password' => 'password123',
    ])->assertRedirect(route('mfa.verify'));

    $this->post(route('mfa.verify.submit'), ['code' => app(Google2FA::class)->getCurrentOtp($secret)])
        ->assertRedirect('/admin');
    $this->assertAuthenticatedAs($user);

    $this->get('/admin')
        ->assertOk()
        ->assertSee('adm-dashboard-entrance', false);

    $this->get('/admin')
        ->assertOk()
        ->assertDontSee('adm-dashboard-entrance', false);
});

it('preserves the dashboard entrance animation for the ajax login redirect', function () {
    $user = User::factory()->create([
        'email' => 'ajax-admin@example.com',
        'password' => Hash::make('password123'),
        'is_admin' => true,
    ]);
    $secret = Mfa::startSetup($user);
    Mfa::enable($user, app(Google2FA::class)->getCurrentOtp($secret));
    $ajax = ['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'];

    $this->withHeaders($ajax)->post('/login', [
        'email' => 'ajax-admin@example.com',
        'password' => 'password123',
    ])->assertOk()->assertJsonPath('redirect', route('mfa.verify'));

    $this->withHeaders($ajax)->post(route('mfa.verify.submit'), ['code' => app(Google2FA::class)->getCurrentOtp($secret)])
        ->assertOk()->assertJsonPath('redirect', route('admin.dashboard'));

    $this->get('/admin')
        ->assertOk()
        ->assertSee('adm-dashboard-entrance', false)
        ->assertSee('data-entrance="true"', false);
});

it('blocks non-admin users from the dashboard', function () {
    $user = User::factory()->create([
        'email' => 'editor@example.com',
        'password' => Hash::make('password123'),
        'is_admin' => false,
    ]);

    $this->actingAs($user);

    $response = $this->get('/admin');

    $response->assertStatus(403);
});
