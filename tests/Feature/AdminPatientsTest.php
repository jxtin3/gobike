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

it('filters patients by an abnormal reading and shows what was abnormal', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $recorder = User::factory()->create(['role' => 'GoBiker']);
    makePatient($recorder, ['name' => 'High Pressure Pedro', 'sys' => 150, 'dia' => 95]);
    makePatient($recorder, ['name' => 'Normal Nena', 'sys' => 118, 'dia' => 76]);
    makePatient($recorder, ['name' => 'Fast Pulse Pablo', 'sys' => 118, 'dia' => 76, 'pulse' => 110]);

    $this->actingAs($admin)
        ->get(route('admin.operations.patients.index', ['reading' => 'bp_high']))
        ->assertOk()
        ->assertSee('High Pressure Pedro')
        ->assertDontSee('Normal Nena')
        ->assertDontSee('Fast Pulse Pablo')
        ->assertSee('Showing patients with high blood pressure')
        ->assertSee('1 patient found');

    $this->actingAs($admin)
        ->get(route('admin.operations.patients.index'))
        ->assertSee('>High BP<', false)
        ->assertSee('>Fast pulse<', false);
});

it('filters patients by the dashboard period', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $recorder = User::factory()->create(['role' => 'GoBiker']);
    makePatient($recorder, ['name' => 'March Maria', 'recorded_at' => '2025-03-10 09:00:00']);
    makePatient($recorder, ['name' => 'April Ana', 'recorded_at' => '2025-04-10 09:00:00']);
    makePatient($recorder, ['name' => 'Last Year Lito', 'recorded_at' => '2024-03-10 09:00:00']);

    $this->actingAs($admin)
        ->get(route('admin.operations.patients.index', ['reading' => 'bp_high', 'year' => 2025, 'month' => 3]))
        ->assertSee('March Maria')
        ->assertSee('in March 2025')
        ->assertDontSee('April Ana')
        ->assertDontSee('Last Year Lito');

    $this->actingAs($admin)
        ->get(route('admin.operations.patients.index', ['year' => 2025]))
        ->assertSee('March Maria')
        ->assertSee('April Ana')
        ->assertDontSee('Last Year Lito');
});

it('rejects an unknown reading filter', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.operations.patients.index', ['reading' => 'bp_extreme']))
        ->assertSessionHasErrors('reading');
});

it('defines every abnormal reading the same way in the database filter and on each record', function () {
    $recorder = User::factory()->create(['role' => 'GoBiker']);

    foreach ([[85, 55, 55, 10, 35.5], [118, 76, 72, 16, 36.6], [140, 80, 101, 21, 37.5], [139, 90, 100, 20, 37.4], [90, 60, 60, 12, 36.0]] as [$sys, $dia, $pulse, $resp, $temp]) {
        makePatient($recorder, compact('sys', 'dia', 'pulse', 'resp', 'temp'));
    }

    foreach (array_keys(Patient::READINGS) as $reading) {
        $fromDatabase = Patient::withReading($reading)->pluck('id')->sort()->values()->all();
        $fromRecords = Patient::all()->filter(fn (Patient $patient) => array_key_exists($reading, $patient->abnormalReadings()))->pluck('id')->sort()->values()->all();

        expect($fromDatabase)->toBe($fromRecords, "Mismatch for {$reading}");
    }
});

it('links the dashboard to the patients behind each reading', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $recorder = User::factory()->create(['role' => 'GoBiker']);
    makePatient($recorder);

    $vitals = $this->actingAs($admin)
        ->get(route('admin.dashboard', ['year' => now()->year, 'month' => now()->month]))
        ->assertOk()
        ->assertSee('data-people-high', false)
        ->viewData('vitals');

    expect($vitals['blood_pressure']['high_url'])->toBe(route('admin.operations.patients.index', ['reading' => 'bp_high', 'year' => now()->year, 'month' => now()->month]))
        ->and($vitals['blood_pressure']['totalHigh'])->toBe(1);
});

it('lists each patient once, with their latest matching check-up, when filtered by a reading', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $recorder = User::factory()->create(['role' => 'GoBiker']);
    makePatient($recorder, ['name' => 'Pedro Santos', 'sys' => 150, 'dia' => 95, 'recorded_at' => now()->subDays(10)]);
    $latest = makePatient($recorder, ['name' => ' pedro santos ', 'sys' => 160, 'dia' => 100, 'recorded_at' => now()->subDay()]);
    makePatient($recorder, ['name' => 'Pedro Santos', 'contact' => '09999999999', 'sys' => 155, 'dia' => 96]); // a different Pedro

    $response = $this->actingAs($admin)
        ->get(route('admin.operations.patients.index', ['reading' => 'bp_high']))
        ->assertOk()
        ->assertSee('2 patients found');

    expect($response->viewData('patients')->total())->toBe(2)
        ->and($response->viewData('patients')->pluck('id'))->toContain($latest->id);

    // Without a reading filter every check-up is listed.
    expect($this->actingAs($admin)->get(route('admin.operations.patients.index'))->viewData('patients')->total())->toBe(3);
});
