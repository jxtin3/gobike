<?php

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows an admin to delete a patient record from its detail page', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $recorder = User::factory()->create(['is_admin' => false]);
    $patient = Patient::create([
        'user_id' => $recorder->id,
        'name' => 'Test Patient',
        'address' => 'Test address',
        'contact' => '09123456789',
        'age' => 45,
        'sys' => 120,
        'dia' => 80,
        'pulse' => 72,
        'resp' => 16,
        'temp' => 36.5,
        'height' => 165,
        'weight' => 65,
        'recorded_at' => now(),
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.operations.patients.destroy', $patient))
        ->assertRedirect(route('admin.operations.patients.index'))
        ->assertSessionHas('success', 'Patient record deleted.');

    $this->assertDatabaseMissing('patients', ['id' => $patient->id]);
});
