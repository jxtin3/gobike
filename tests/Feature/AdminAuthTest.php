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
