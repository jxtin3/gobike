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

    $response = $this->actingAs($admin)->get(route('admin.operations.reports.index', [
        'from' => now()->format('Y-m'),
        'to' => now()->format('Y-m'),
    ]));

    $response->assertOk()
        ->assertSee('Patient check-ups')
        ->assertDontSee('Private Patient Name');

    $charts = $response->viewData('charts');
    expect($charts[0]['total'])->toBe(1)
        ->and($charts[1]['total'])->toBe(1)
        ->and($charts[0]['months'])->toHaveCount(1);
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

it('rejects invalid report ranges', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.operations.reports.index', [
            'from' => now()->format('Y-m'),
            'to' => now()->subMonth()->format('Y-m'),
        ]))
        ->assertSessionHasErrors('from');
});
