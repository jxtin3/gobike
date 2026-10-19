<?php

use App\Models\User;

function makeLoginAdmin(): User
{
    return User::factory()->create([
        'email' => 'admin@example.com',
        'password' => bcrypt('correct-password'),
        'is_admin' => true,
    ]);
}

it('blocks login after too many failed attempts, even with the right password', function () {
    makeLoginAdmin();

    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', ['email' => 'admin@example.com', 'password' => 'wrong'])
            ->assertSessionHasErrors('email');
    }

    $this->post('/login', ['email' => 'admin@example.com', 'password' => 'correct-password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('answers 429 to the ajax login when the limit is hit', function () {
    makeLoginAdmin();

    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/login', ['email' => 'admin@example.com', 'password' => 'wrong'])
            ->assertStatus(422);
    }

    $this->postJson('/login', ['email' => 'admin@example.com', 'password' => 'correct-password'])
        ->assertStatus(429);
});

it('still lets the admin in after a few mistakes', function () {
    makeLoginAdmin();

    for ($i = 0; $i < 4; $i++) {
        $this->post('/login', ['email' => 'admin@example.com', 'password' => 'wrong']);
    }

    $this->post('/login', ['email' => 'admin@example.com', 'password' => 'correct-password'])
        ->assertRedirect();

    $this->assertAuthenticated();
});