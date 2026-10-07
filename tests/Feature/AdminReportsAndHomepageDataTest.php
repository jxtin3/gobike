<?php

use App\Models\HomePartner;
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

    $response = $this->actingAs($admin)->get(route('admin.operations.reports.index', [
        'month' => now()->format('Y-m'),
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
        ->assertSeeInOrder([
            'aria-label="GoBiker statuses"',
            'Emergency',
            'Responding',
            'GoBikers',
        ]);
});

it('allows admins to edit homepage data and shows the changes publicly', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $partner = HomePartner::firstOrFail();
    $stat = ImpactStat::where('section', 'impact')->firstOrFail();
    $highlight = ImpactStat::where('section', 'highlight')->firstOrFail();
    $badge = ImpactStat::where('section', 'badge')->firstOrFail();

    $this->actingAs($admin)
        ->get(route('admin.operations.homepage-data.index'))
        ->assertOk()
        ->assertSee('Featured impact highlights');

    $this->put(route('admin.operations.homepage-data.partners.update', $partner), [
        'name' => 'Community Health Partner',
        'abbreviation' => 'CHP',
    ])
        ->assertRedirect(route('admin.operations.homepage-data.index'));

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
        ->assertSee('Community Health Partner')
        ->assertSee('CHP')
        ->assertSee('People served')
        ->assertSee('3,000+')
        ->assertSee('Missions completed')
        ->assertSee('350+')
        ->assertSee('Families supported')
        ->assertSee('600+');
});

it('rejects invalid report months', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.operations.reports.index', [
            'month' => 'not-a-month',
        ]))
        ->assertSessionHasErrors('month');
});
