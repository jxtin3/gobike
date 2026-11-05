<?php

use App\Models\Appointment;
use App\Models\SosRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->gobiker = User::factory()->create(['name' => 'Maria Santos', 'role' => 'GoBiker', 'status' => 'Active', 'barangay' => 'Poblacion']);
    $this->colleague = User::factory()->create(['name' => 'Jose Lim', 'role' => 'GoBiker', 'status' => 'Active', 'barangay' => 'Poblacion']);
    $this->resident = User::factory()->create(['name' => 'Ana Reyes', 'role' => 'User', 'status' => 'Active', 'barangay' => 'Poblacion', 'mobile' => '09170001111']);
});

function historyVisit(User $resident, User $gobiker, string $status, array $extra = []): Appointment
{
    return Appointment::create($extra + [
        'resident_id' => $resident->id, 'gobiker_id' => $gobiker->id, 'barangay' => 'Poblacion',
        'address' => 'Purok 3', 'preferred_date' => now()->addDay()->toDateString(), 'preferred_window' => 'morning',
        'reason' => 'Blood pressure check', 'status' => $status,
    ]);
}

function historySos(User $resident, ?User $gobiker, string $status, array $extra = []): SosRequest
{
    return SosRequest::create($extra + [
        'user_id' => $resident->id, 'municipality' => 'Bugallon', 'barangay' => 'Poblacion', 'address' => '12 Rizal St.',
        'landmark' => 'Chapel', 'latitude' => 15.95, 'longitude' => 120.18, 'status' => $status,
        'assigned_gobiker_id' => $gobiker?->id, 'resolved_at' => in_array($status, ['resolved', 'cancelled']) ? now() : null,
    ]);
}

it('lists the home visits a GoBiker finished, declined or had cancelled, and nothing still open', function () {
    historyVisit($this->resident, $this->gobiker, 'completed', ['completed_at' => now()]);
    historyVisit($this->resident, $this->gobiker, 'declined', ['decline_note' => 'Away that week.']);
    historyVisit($this->resident, $this->gobiker, 'cancelled');
    historyVisit($this->resident, $this->gobiker, 'pending');
    historyVisit($this->resident, $this->gobiker, 'accepted');

    Sanctum::actingAs($this->gobiker);
    $visits = $this->getJson(route('api.gobiker.history'))->assertOk()->json('appointments');

    expect(collect($visits)->pluck('status')->sort()->values()->all())->toBe(['cancelled', 'completed', 'declined']);
    $declined = collect($visits)->firstWhere('status', 'declined');
    expect($declined['decline_note'])->toBe('Away that week.')
        ->and($declined['resident_name'])->toBe('Ana Reyes')
        ->and($declined['updated_at'])->not->toBeNull();
});

it('shows a GoBiker only their own history, never a colleague\'s', function () {
    historyVisit($this->resident, $this->colleague, 'completed', ['completed_at' => now()]);
    historySos($this->resident, $this->colleague, 'resolved');

    Sanctum::actingAs($this->gobiker);
    $this->getJson(route('api.gobiker.history'))
        ->assertOk()
        ->assertJsonCount(0, 'appointments')
        ->assertJsonCount(0, 'sos');
});

it('lists the emergencies a GoBiker answered, finished only, without the exact location', function () {
    historySos($this->resident, $this->gobiker, 'resolved', ['description' => 'Fell at home']);
    historySos($this->resident, $this->gobiker, 'cancelled');
    historySos($this->resident, $this->gobiker, 'responding'); // still open
    historySos($this->resident, null, 'resolved'); // nobody was assigned

    Sanctum::actingAs($this->gobiker);
    $sos = $this->getJson(route('api.gobiker.history'))->assertOk()->json('sos');

    expect($sos)->toHaveCount(2);
    foreach ($sos as $item) {
        expect($item)->not->toHaveKeys(['latitude', 'longitude', 'mobile'])
            ->and($item['resident_name'])->toBe('Ana Reyes')
            // Older apps read anything but "resolved" as still open: a cancelled one says resolved.
            ->and($item['status'])->toBe('resolved');
    }
    expect(collect($sos)->where('cancelled', true))->toHaveCount(1);
});

it('lists the newest first, and no more than 100 of each', function () {
    foreach (range(1, 103) as $i) {
        historyVisit($this->resident, $this->gobiker, 'completed', [
            'reason' => "Visit {$i}", 'completed_at' => now()->subMinutes(200 - $i), 'updated_at' => now()->subMinutes(200 - $i),
        ]);
    }

    Sanctum::actingAs($this->gobiker);
    $visits = $this->getJson(route('api.gobiker.history'))->assertOk()->json('appointments');

    expect($visits)->toHaveCount(100)
        ->and($visits[0]['reason'])->toBe('Visit 103')
        ->and($visits[99]['reason'])->toBe('Visit 4');
});

it('is empty for a GoBiker with nothing finished', function () {
    Sanctum::actingAs($this->gobiker);

    $this->getJson(route('api.gobiker.history'))
        ->assertOk()
        ->assertExactJson(['appointments' => [], 'sos' => []]);
});

it('is for GoBikers only', function () {
    $this->getJson(route('api.gobiker.history'))->assertUnauthorized();

    Sanctum::actingAs($this->resident);
    $this->getJson(route('api.gobiker.history'))->assertForbidden();
});
