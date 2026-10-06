<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('shows the admin login page', function () {
    $response = $this->get('/login');

    $response->assertStatus(200)
        ->assertSee('Welcome back');
});

it('allows an admin user to access the dashboard', function () {
    $user = User::factory()->create([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'password' => Hash::make('password123'),
        'is_admin' => true,
    ]);

    $response = $this->post('/login', [
        'email' => 'admin@example.com',
        'password' => 'password123',
    ]);

    $response->assertRedirect('/admin');
    $this->assertAuthenticatedAs($user);

    $this->get('/admin')
        ->assertOk()
        ->assertSee('adm-dashboard-entrance', false);

    $this->get('/admin')
        ->assertOk()
        ->assertDontSee('adm-dashboard-entrance', false);
});

it('preserves the dashboard entrance animation for the ajax login redirect', function () {
    User::factory()->create([
        'email' => 'ajax-admin@example.com',
        'password' => Hash::make('password123'),
        'is_admin' => true,
    ]);

    $this->withHeaders([
        'Accept' => 'application/json',
        'X-Requested-With' => 'XMLHttpRequest',
    ])->post('/login', [
        'email' => 'ajax-admin@example.com',
        'password' => 'password123',
    ])->assertOk()->assertJsonPath('redirect', route('admin.dashboard'));

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
