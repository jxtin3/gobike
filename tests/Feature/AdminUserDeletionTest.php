<?php

use App\Models\GobikerMessage;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('will not delete a GoBiker who recorded patient check-ups', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $gobiker = User::factory()->create(['role' => 'GoBiker', 'name' => 'Rider One']);
    $patient = Patient::create([
        'user_id' => $gobiker->id, 'name' => 'Pedro', 'address' => 'Purok 1', 'contact' => '09123456789',
        'age' => 60, 'sys' => 120, 'dia' => 80, 'pulse' => 70, 'resp' => 16, 'temp' => 36.6,
        'recorded_at' => now(),
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.operations.users.destroy', $gobiker))
        ->assertRedirect()
        ->assertSessionHas('error', 'Rider One has recorded 1 patient record. Change the role to User instead of deleting the account.');

    $this->assertModelExists($gobiker);
    $this->assertModelExists($patient);
});

it('deletes a user without patient records and signs them out of the app', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $gobiker = User::factory()->create(['role' => 'GoBiker']);
    $gobiker->createToken('mobile');
    GobikerMessage::create(['user_id' => $gobiker->id, 'message' => 'Hello']);

    $this->actingAs($admin)
        ->delete(route('admin.operations.users.destroy', $gobiker))
        ->assertRedirect(route('admin.operations.users.index'))
        ->assertSessionHas('success', 'User deleted.');

    $this->assertModelMissing($gobiker);
    $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $gobiker->id]);
});

it('signs a user out of the mobile app when an admin sets a new password', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $gobiker = User::factory()->create(['role' => 'GoBiker', 'mobile' => null, 'barangay' => null]);
    $gobiker->createToken('mobile');

    $this->actingAs($admin)
        ->put(route('admin.operations.users.update', $gobiker), [
            'name' => $gobiker->name,
            'email' => $gobiker->email,
            'role' => 'GoBiker',
            'password' => 'brand-new-pass-1',
            'password_confirmation' => 'brand-new-pass-1',
        ])
        ->assertRedirect(route('admin.operations.users.show', $gobiker));

    expect($gobiker->tokens()->count())->toBe(0);
});

it('keeps app sessions when the password is not changed', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $gobiker = User::factory()->create(['role' => 'GoBiker']);
    $gobiker->createToken('mobile');

    $this->actingAs($admin)
        ->put(route('admin.operations.users.update', $gobiker), [
            'name' => 'Renamed',
            'email' => $gobiker->email,
            'role' => 'GoBiker',
        ])
        ->assertRedirect();

    expect($gobiker->tokens()->count())->toBe(1);
});

it('requires strong passwords for new accounts and stores emails in lowercase', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $payload = ['name' => 'New User', 'email' => 'New.User@Example.com', 'role' => 'User'];

    $this->actingAs($admin)
        ->post(route('admin.operations.users.store'), $payload + ['password' => 'short', 'password_confirmation' => 'short'])
        ->assertSessionHasErrors('password');

    $this->actingAs($admin)
        ->post(route('admin.operations.users.store'), $payload + ['password' => 'long-enough-pass-9', 'password_confirmation' => 'long-enough-pass-9'])
        ->assertRedirect(route('admin.operations.users.index'));

    $this->assertDatabaseHas('users', ['email' => 'new.user@example.com', 'status' => 'Active']);
});
