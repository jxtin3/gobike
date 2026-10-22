<?php

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makePatient(User $recorder, array $overrides = []): Patient
{
    return Patient::create(array_merge([
        'user_id' => $recorder->id,
        'barangay' => 'Poblacion',
        'name' => 'Pedro Santos',
        'address' => 'Purok 1',
        'contact' => '09123456789',
        'age' => 62,
        'sys' => 150,
        'dia' => 95,
        'pulse' => 80,
        'resp' => 18,
        'temp' => 36.8,
        'height' => 165,
        'weight' => 60,
        'recorded_at' => now(),
    ], $overrides));
}

it('lists patient records', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $recorder = User::factory()->create(['role' => 'GoBiker']);
    $patient = makePatient($recorder);

    $this->actingAs($admin)
        ->get(route('admin.operations.patients.index'))
        ->assertOk()
        ->assertSee('Pedro Santos')
        ->assertSee(route('admin.operations.patients.show', $patient), false);
});

it('searches patients by name and filters by barangay', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $recorder = User::factory()->create(['role' => 'GoBiker']);
    makePatient($recorder);
    makePatient($recorder, ['name' => 'Maria Lopez', 'barangay' => 'San Isidro']);

    $this->actingAs($admin)
        ->get(route('admin.operations.patients.index', ['search' => 'Maria']))
        ->assertSee('Maria Lopez')
        ->assertDontSee('Pedro Santos');

    $this->actingAs($admin)
        ->get(route('admin.operations.patients.index', ['barangay' => 'Poblacion']))
        ->assertSee('Pedro Santos')
        ->assertDontSee('Maria Lopez');
});

it('shows a patient record with who recorded it', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $recorder = User::factory()->create(['role' => 'GoBiker', 'name' => 'Rider One']);
    $patient = makePatient($recorder);

    $this->actingAs($admin)
        ->get(route('admin.operations.patients.show', $patient))
        ->assertOk()
        ->assertSee('Pedro Santos')
        ->assertSee('150/95 mmHg')
        ->assertSee('Rider One');
});
