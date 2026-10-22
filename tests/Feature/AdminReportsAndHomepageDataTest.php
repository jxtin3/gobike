<?php

use App\Models\ImpactStat;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows monthly aggregated reports without patient identity data', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $recorder = User::factory()->create(['is_admin' => false, 'role' => 'GoBiker']);
    Patient::create([
        'user_id' => $recorder->id,
        'name' => 'Private Patient Name',
        'address' => 'Private address',
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
    Patient::create([
        'user_id' => $recorder->id,
        'name' => 'High readings patient',
        'address' => 'Private address',
        'contact' => '09123456789',
        'age' => 45,
        'sys' => 150,
        'dia' => 95,
        'pulse' => 110,
        'resp' => 24,
        'temp' => 38,
        'height' => 170,
        'weight' => 70,
        'recorded_at' => now(),
    ]);
    Patient::create([
        'user_id' => $recorder->id,
        'name' => 'Low readings patient',
        'address' => 'Private address',
        'contact' => '09123456789',
        'age' => 45,
        'sys' => 85,
        'dia' => 55,
        'pulse' => 55,
        'resp' => 10,
        'temp' => 35.5,
        'height' => 170,
        'weight' => 70,
        'recorded_at' => now()->startOfYear()->addMonths(6),
    ]);

    $response = $this->actingAs($admin)->get(route('admin.dashboard', [
        'year' => now()->year,
    ]));

    $response->assertOk()
        ->assertSee('Patient check-ups')
        ->assertSee('Blood pressure')
        ->assertSee('Abnormal readings by vital sign')
        ->assertSee('data-checkups=', false)
        ->assertDontSee('Private Patient Name');

    $analytics = $response->viewData();
    $vitals = $analytics['vitals'];
    expect($analytics['checkupsTotal'])->toBe(3)
        ->and($analytics['checkups'])->toHaveCount(12)
        ->and($analytics['checkups'][0]['shortLabel'])->toBe('Jan')
        ->and($analytics['checkups'][11]['shortLabel'])->toBe('Dec')
        ->and($vitals['blood_pressure']['totalHigh'])->toBe(1)
        ->and($vitals['blood_pressure']['totalLow'])->toBe(1)
        ->and($vitals['pulse']['totalHigh'])->toBe(1)
        ->and($vitals['pulse']['totalLow'])->toBe(1)
        ->and($vitals['respiration']['totalHigh'])->toBe(1)
        ->and($vitals['respiration']['totalLow'])->toBe(1)
        ->and($vitals['temperature']['totalHigh'])->toBe(1)
        ->and($vitals['temperature']['totalLow'])->toBe(1)
        ->and($analytics['pie'])->toHaveCount(4)
        ->and($analytics['pie'][0]['count'])->toBe(2)
        ->and($analytics['pie'][0]['high'])->toBe(1)
        ->and($analytics['pie'][0]['low'])->toBe(1);
});

it('makes analytics the admin dashboard and places live operations after the charts', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Analytics dashboard')
        ->assertSee('Choose a vital sign')
        ->assertSee('data-bar-chart', false)
        ->assertSee('data-line-chart', false)
        ->assertSee('data-pie-chart', false)
        ->assertDontSee('id="map"', false)
        ->assertDontSee('adm-dashboard-entrance', false)
        ->assertDontSee('>Operations</span>', false)
        ->assertDontSee('>Reports</span>', false)
        ->assertSeeInOrder([
            'Abnormal readings by vital sign',
            'Monthly activity',
            'Vital-sign screening',
        ]);

    $this->get(route('admin.live-map.index'))
        ->assertOk()
        ->assertSee('id="map"', false)
        ->assertSee('data-locations-url="'.route('admin.locations.index').'"', false)
        ->assertSeeInOrder([
            'rider-legend',
            'Emergency',
            'Responding',
            'GoBikers',
        ]);
});

it('allows admins to edit homepage data and shows the changes publicly', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $stat = ImpactStat::where('section', 'impact')->firstOrFail();
    $highlight = ImpactStat::where('section', 'highlight')->firstOrFail();
    $badge = ImpactStat::where('section', 'badge')->firstOrFail();

    $this->actingAs($admin)
        ->get(route('admin.operations.homepage-data.index'))
        ->assertOk()
        ->assertSee('Featured impact highlights');

    $this->put(route('admin.operations.homepage-data.impact-stats.update', $stat), [
        'label' => 'People served',
        'value' => '3,000+',
    ])->assertRedirect(route('admin.operations.homepage-data.index'));

    $this->put(route('admin.operations.homepage-data.impact-stats.update', $highlight), [
        'label' => 'Missions completed',
        'value' => '350+',
    ])->assertRedirect(route('admin.operations.homepage-data.index'));

    $this->put(route('admin.operations.homepage-data.impact-stats.update', $badge), [
        'label' => 'Families supported',
        'value' => '600+',
    ])->assertRedirect(route('admin.operations.homepage-data.index'));

    $this->get('/')
        ->assertOk()
        ->assertSee('People served')
        ->assertSee('3,000+')
        ->assertSee('Missions completed')
        ->assertSee('350+')
        ->assertSee('Families supported')
        ->assertSee('600+');
});

it('adds older years that have patient records to the year filter', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $recorder = User::factory()->create(['role' => 'GoBiker']);
    Patient::create([
        'user_id' => $recorder->id, 'name' => 'Very old record', 'address' => 'Somewhere', 'contact' => '09123456789',
        'age' => 40, 'sys' => 120, 'dia' => 80, 'pulse' => 70, 'resp' => 16, 'temp' => 36.6,
        'height' => 160, 'weight' => 60, 'recorded_at' => now()->subYears(7),
    ]);

    $years = $this->actingAs($admin)->get(route('admin.dashboard'))->viewData('years');

    expect($years)->toBe(range(now()->year, now()->year - 7));
});

it('rejects invalid dashboard years', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard', ['year' => 'not-a-year']))
        ->assertSessionHasErrors('year');

    $this->actingAs($admin)
        ->get(route('admin.dashboard', ['year' => now()->year + 1]))
        ->assertSessionHasErrors('year');
});

it('offers the last five years in the dashboard year filter', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $recorder = User::factory()->create(['role' => 'GoBiker']);
    Patient::create([
        'user_id' => $recorder->id, 'name' => 'Old record', 'address' => 'Somewhere', 'contact' => '09123456789',
        'age' => 40, 'sys' => 120, 'dia' => 80, 'pulse' => 70, 'resp' => 16, 'temp' => 36.6,
        'height' => 160, 'weight' => 60, 'recorded_at' => now()->subYears(2),
    ]);

    $response = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();

    expect($response->viewData('years'))->toBe(range(now()->year, now()->year - 4))
        ->and($response->viewData('year'))->toBe(now()->year);
});

it('explains the dashboard cards in plain language', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $recorder = User::factory()->create(['role' => 'GoBiker']);
    $reading = fn (string $name, array $overrides = []) => Patient::create(array_merge([
        'user_id' => $recorder->id, 'name' => $name, 'address' => 'Purok 1', 'contact' => '09123456789',
        'age' => 50, 'sys' => 118, 'dia' => 76, 'pulse' => 72, 'resp' => 16, 'temp' => 36.6,
        'height' => 160, 'weight' => 60, 'recorded_at' => now(),
    ], $overrides));

    foreach (['Ana', 'Ben', 'Carla'] as $name) {
        $reading($name);
    }
    $reading('Dino', ['sys' => 150, 'dia' => 95]); // 1 of 4 patients has high blood pressure
    $reading('Dino', ['sys' => 155, 'dia' => 98]); // checked again: still one person

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('GoBikers recorded', false)
        ->assertSee('5 check-ups')
        ->assertSee('4 patients')
        ->assertSee('25% of 4 patients had high blood pressure')
        ->assertSee('None of the 4 patients had a fast pulse')
        ->assertSee('Normal: 90/60 to 139/89')
        ->assertSee('What counts as normal, high or low?');
});

it('counts a patient once on the dashboard however many times they were checked', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $recorder = User::factory()->create(['role' => 'GoBiker']);
    foreach ([1, 2, 3] as $ignored) {
        Patient::create([
            'user_id' => $recorder->id, 'name' => 'Same Person', 'address' => 'Purok 1', 'contact' => '09123456789',
            'age' => 50, 'sys' => 150, 'dia' => 95, 'pulse' => 72, 'resp' => 16, 'temp' => 36.6,
            'height' => 160, 'weight' => 60, 'recorded_at' => now(),
        ]);
    }

    $vitals = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->viewData('vitals');

    expect($vitals['blood_pressure']['peopleHigh'])->toBe(1)
        ->and($vitals['blood_pressure']['totalHigh'])->toBe(3); // charts still count check-ups
});

it('tells the admin when no check-ups were recorded', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('No patient check-ups were recorded in '.now()->year.' yet')
        ->assertSee('No patients checked yet');
});

it('narrows the dashboard to one month and charts it day by day', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $recorder = User::factory()->create(['role' => 'GoBiker']);
    $reading = fn (string $date, array $overrides = []) => Patient::create(array_merge([
        'user_id' => $recorder->id, 'name' => 'Patient', 'address' => 'Purok 1', 'contact' => '09123456789',
        'age' => 50, 'sys' => 118, 'dia' => 76, 'pulse' => 72, 'resp' => 16, 'temp' => 36.6,
        'height' => 160, 'weight' => 60, 'recorded_at' => $date,
    ], $overrides));

    $year = now()->year - 1;
    $reading("{$year}-03-05 09:00:00");
    $reading("{$year}-03-05 10:00:00", ['sys' => 150, 'dia' => 95]);
    $reading("{$year}-03-20 10:00:00");
    $reading("{$year}-04-02 10:00:00"); // another month, must be left out

    $response = $this->actingAs($admin)
        ->get(route('admin.dashboard', ['year' => $year, 'month' => 3]))
        ->assertOk()
        ->assertSee('GoBikers recorded', false)
        ->assertSee('3 check-ups')
        ->assertSee('March '.$year)
        ->assertSee('Daily activity');

    expect($response->viewData('checkups'))->toHaveCount(31)
        ->and($response->viewData('checkups')[4]['count'])->toBe(2) // March 5
        ->and($response->viewData('checkupsTotal'))->toBe(3)
        ->and($response->viewData('vitals')['blood_pressure']['totalHigh'])->toBe(1);

    // "All months" still charts the full year, month by month.
    $all = $this->actingAs($admin)->get(route('admin.dashboard', ['year' => $year]))->assertOk();
    expect($all->viewData('checkups'))->toHaveCount(12)
        ->and($all->viewData('checkupsTotal'))->toBe(4);
});

it('rejects an invalid dashboard month', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->get(route('admin.dashboard', ['month' => 13]))->assertSessionHasErrors('month');
});
