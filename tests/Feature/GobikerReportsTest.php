<?php

use App\Models\Patient;
use App\Models\Ronda;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function reportCheckup(User $gobiker, string $when, array $overrides = []): Patient
{
    return Patient::create(array_merge([
        'user_id' => $gobiker->id, 'name' => 'Ana Reyes', 'address' => 'Purok 1', 'contact' => '09170000001',
        'age' => 60, 'sys' => 118, 'dia' => 76, 'pulse' => 72, 'resp' => 16, 'temp' => 36.6,
        'height' => 160, 'weight' => 60, 'recorded_at' => $when,
    ], $overrides));
}

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-22 15:00:00'));
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->rider = User::factory()->create(['role' => 'GoBiker', 'name' => 'Rider One', 'barangay' => 'Poblacion']);
    $this->idle = User::factory()->create(['role' => 'GoBiker', 'name' => 'Idle Rider']);
});

it('counts rondas, time, check-ups and people per GoBiker for a month', function () {
    Ronda::create(['user_id' => $this->rider->id, 'started_at' => '2026-10-05 08:00', 'ended_at' => '2026-10-05 10:30']);
    Ronda::create(['user_id' => $this->rider->id, 'started_at' => '2026-10-12 08:00', 'ended_at' => '2026-10-12 09:00']);
    Ronda::create(['user_id' => $this->rider->id, 'started_at' => '2026-09-28 08:00', 'ended_at' => '2026-09-28 09:00']); // September
    reportCheckup($this->rider, '2026-10-05 08:30');
    reportCheckup($this->rider, '2026-10-12 08:30', ['sys' => 150, 'dia' => 95]); // same person again, high BP
    reportCheckup($this->rider, '2026-10-12 08:45', ['name' => 'Ben Cruz', 'contact' => '09170000002']);

    $response = $this->actingAs($this->admin)
        ->get(route('admin.operations.gobiker-reports.index', ['period' => 'month', 'date' => '2026-10-01']))
        ->assertOk()
        ->assertSee('October 2026')
        ->assertSee('Rider One')
        ->assertSee('Idle Rider'); // GoBikers with no activity are listed too

    $row = $response->viewData('rows')->firstWhere('user.id', $this->rider->id);
    expect($row)
        ->rondas->toBe(2)
        ->minutes->toBe(210)
        ->checkups->toBe(3)
        ->people->toBe(2)
        ->abnormal->toBe(1)
        ->and($response->viewData('rows')->first()['user']->id)->toBe($this->rider->id) // busiest first
        ->and($response->viewData('totals')['active'])->toBe(1);
});

it('reports a single week and moves between weeks', function () {
    reportCheckup($this->rider, '2026-10-20 09:00'); // Tue of week Oct 19-25
    reportCheckup($this->rider, '2026-10-12 09:00'); // previous week

    $response = $this->actingAs($this->admin)
        ->get(route('admin.operations.gobiker-reports.index', ['period' => 'week', 'date' => '2026-10-22']))
        ->assertOk()
        ->assertSee('Oct 19 – 25, 2026')
        ->assertSee(e(route('admin.operations.gobiker-reports.index', ['period' => 'week', 'date' => '2026-10-12'])), false);

    expect($response->viewData('rows')->firstWhere('user.id', $this->rider->id)['checkups'])->toBe(1);
});

it('shows one GoBiker report with a breakdown, rondas and check-ups', function () {
    Ronda::create(['user_id' => $this->rider->id, 'barangay' => 'Poblacion', 'started_at' => '2026-10-20 08:00', 'ended_at' => '2026-10-20 09:15']);
    Ronda::create(['user_id' => $this->rider->id, 'started_at' => '2026-10-21 08:00']); // never stopped
    reportCheckup($this->rider, '2026-10-20 08:30', ['temp' => 38.2]);

    $response = $this->actingAs($this->admin)
        ->get(route('admin.operations.gobiker-reports.show', [$this->rider, 'period' => 'week', 'date' => '2026-10-20']))
        ->assertOk()
        ->assertSee('Rider One')
        ->assertSee('Day by day')
        ->assertSee('Not stopped')
        ->assertSee('1 h 15 min')
        ->assertSee('High temp')
        ->assertSee('data-print', false);

    $report = $response->viewData('report');
    expect($report['rondas'])->toBe(2)
        ->and($report['breakdown'])->toHaveCount(7)
        ->and($report['breakdown'][1]['rondas'])->toBe(1) // Tuesday Oct 20
        ->and($report['breakdown'][1]['checkups'])->toBe(1);
});

it('breaks a month down week by week', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('admin.operations.gobiker-reports.show', [$this->rider, 'period' => 'month', 'date' => '2026-10-01']))
        ->assertOk()
        ->assertSee('Week by week');

    // Oct 2026: Thu 1 – Sun 4, then four full weeks, then Mon 26 – Sat 31
    expect($response->viewData('report')['breakdown']->pluck('label')->all())
        ->toBe(['Oct 1 – 4', 'Oct 5 – 11', 'Oct 12 – 18', 'Oct 19 – 25', 'Oct 26 – 31']);
});

it('downloads the reports as CSV', function () {
    reportCheckup($this->rider, '2026-10-05 08:30');

    $summary = $this->actingAs($this->admin)
        ->get(route('admin.operations.gobiker-reports.index', ['period' => 'month', 'date' => '2026-10-01', 'format' => 'csv']))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8')
        ->assertDownload('gobiker-report-month-2026-10-01.csv')
        ->streamedContent();

    expect($summary)->toStartWith("\xEF\xBB\xBF")
        ->toContain('GoBiker,Email,Barangay,Rondas')
        ->toContain('"Rider One"');

    $single = $this->actingAs($this->admin)
        ->get(route('admin.operations.gobiker-reports.show', [$this->rider, 'period' => 'month', 'date' => '2026-10-01', 'format' => 'csv']))
        ->assertOk()
        ->streamedContent();

    expect($single)->toContain('Check-up,"2026-10-05 08:30","Ana Reyes"');
});

it('only reports on GoBikers and only for admins', function () {
    $plainUser = User::factory()->create(['role' => 'User']);

    $this->actingAs($this->admin)->get(route('admin.operations.gobiker-reports.show', $plainUser))->assertNotFound();
    $this->actingAs($this->rider)->get(route('admin.operations.gobiker-reports.index'))->assertForbidden();
});

it('rejects an invalid period', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.operations.gobiker-reports.index', ['period' => 'year']))
        ->assertSessionHasErrors('period');
});
