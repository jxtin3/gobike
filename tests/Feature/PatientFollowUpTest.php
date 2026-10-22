<?php

use App\Models\Patient;
use App\Models\PatientFollowUp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function followUpCheckup(User $gobiker, int $daysAgo, array $overrides = []): Patient
{
    return Patient::create(array_merge([
        'user_id' => $gobiker->id, 'name' => 'Lola Nena', 'address' => 'Purok 2', 'contact' => '09181112222',
        'age' => 72, 'sys' => 120, 'dia' => 78, 'pulse' => 70, 'resp' => 16, 'temp' => 36.5,
        'height' => 150, 'weight' => 52, 'recorded_at' => now()->subDays($daysAgo),
    ], $overrides));
}

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true, 'name' => 'Admin Ana']);
    $this->gobiker = User::factory()->create(['role' => 'GoBiker', 'status' => 'Active']);
});

it('suggests stopping when the last three check-ups were normal', function () {
    followUpCheckup($this->gobiker, 90, ['sys' => 150, 'dia' => 95]); // older, abnormal: not among the last 3
    followUpCheckup($this->gobiker, 60);
    followUpCheckup($this->gobiker, 30);
    $latest = followUpCheckup($this->gobiker, 1);

    $this->actingAs($this->admin)
        ->get(route('admin.operations.patients.show', $latest))
        ->assertOk()
        ->assertSee('Normal on the last 3 check-ups')
        ->assertSee('All check-ups')
        ->assertSee('No decision yet');

    $this->actingAs($this->admin)
        ->get(route('admin.operations.patients.index'))
        ->assertSee('Can stop?');
});

it('says to keep checking when a recent check-up was abnormal', function () {
    followUpCheckup($this->gobiker, 60);
    followUpCheckup($this->gobiker, 30, ['temp' => 38.4]);
    $latest = followUpCheckup($this->gobiker, 1);

    $this->actingAs($this->admin)
        ->get(route('admin.operations.patients.show', $latest))
        ->assertSee('Keep checking:')
        ->assertSee('High temp on '.now()->subDays(30)->format('M j, Y'));
});

it('waits for enough check-ups before suggesting anything', function () {
    followUpCheckup($this->gobiker, 30);
    $latest = followUpCheckup($this->gobiker, 1);

    $this->actingAs($this->admin)
        ->get(route('admin.operations.patients.show', $latest))
        ->assertSee('Not enough check-ups yet')
        ->assertSee('(2 of 3)');
});

it('lets the admin decide, for every check-up of the person', function () {
    $first = followUpCheckup($this->gobiker, 30);
    $second = followUpCheckup($this->gobiker, 1, ['name' => ' lola nena ']);

    $this->actingAs($this->admin)
        ->patch(route('admin.operations.patients.follow-up', $first), ['decision' => 'stop', 'note' => 'Normal since July.'])
        ->assertRedirect()
        ->assertSessionHas('success');

    $followUp = PatientFollowUp::sole();
    expect($followUp->decision)->toBe('stop')
        ->and($followUp->decided_by)->toBe($this->admin->id)
        ->and($second->followUp()?->is($followUp))->toBeTrue();

    $this->actingAs($this->admin)
        ->get(route('admin.operations.patients.show', $second))
        ->assertSee('Stop checking · decided')
        ->assertSee('by Admin Ana')
        ->assertSee('Normal since July.');

    // Changing the decision updates the same record.
    $this->actingAs($this->admin)
        ->patch(route('admin.operations.patients.follow-up', $second), ['decision' => 'continue'])
        ->assertRedirect();

    expect(PatientFollowUp::count())->toBe(1)
        ->and(PatientFollowUp::sole()->decision)->toBe('continue');
});

it('rejects an unknown follow-up decision', function () {
    $checkup = followUpCheckup($this->gobiker, 1);

    $this->actingAs($this->admin)
        ->patch(route('admin.operations.patients.follow-up', $checkup), ['decision' => 'maybe'])
        ->assertSessionHasErrors('decision');
});

it('gives the decision to the GoBiker app with each check-up', function () {
    $checkup = followUpCheckup($this->gobiker, 1);
    PatientFollowUp::create([
        'person_key' => $checkup->personKey(), 'name' => 'Lola Nena', 'contact' => '09181112222',
        'decision' => 'stop', 'note' => 'All normal.', 'decided_by' => $this->admin->id, 'decided_at' => now(),
    ]);
    followUpCheckup($this->gobiker, 2, ['name' => 'Someone Else', 'contact' => '09000000000']);

    Sanctum::actingAs($this->gobiker);

    $patients = collect($this->getJson(route('api.gobiker.patients.index'))->assertOk()->json('patients'));

    expect($patients->firstWhere('name', 'Lola Nena')['follow_up'])->toMatchArray(['decision' => 'stop', 'note' => 'All normal.'])
        ->and($patients->firstWhere('name', 'Someone Else')['follow_up'])->toBeNull();
});

it('downloads all of a person\'s check-ups as CSV', function () {
    followUpCheckup($this->gobiker, 30, ['sys' => 150, 'dia' => 95]);
    $latest = followUpCheckup($this->gobiker, 1);

    $csv = $this->actingAs($this->admin)
        ->get(route('admin.operations.patients.show', [$latest, 'format' => 'csv']))
        ->assertOk()
        ->assertDownload('lola-nena-check-ups.csv')
        ->streamedContent();

    expect(substr_count($csv, 'Lola Nena'))->toBe(2)
        ->and($csv)->toContain('"High BP"')
        ->toContain(',Normal,');
});
