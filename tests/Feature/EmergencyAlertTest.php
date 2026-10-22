<?php

use App\Models\Location;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->gobiker = User::factory()->create([
        'name' => 'Juan Dela Cruz',
        'role' => 'GoBiker',
        'status' => 'Active',
        'mobile' => '09171234567',
    ]);
});

function pressEmergency(User $gobiker): Location
{
    Sanctum::actingAs($gobiker);
    test()->postJson(route('api.gobiker.emergency'), ['latitude' => 16.04, 'longitude' => 120.33])->assertOk();

    return Location::where('user_id', $gobiker->id)->sole();
}

function adminEmergencies(User $admin): array
{
    return test()->actingAs($admin, 'web')
        ->getJson(route('admin.emergencies.index'))
        ->assertOk()
        ->json('emergencies');
}

it('is only for admins', function () {
    $this->getJson(route('admin.emergencies.index'))->assertUnauthorized();

    $this->actingAs($this->gobiker, 'web')->getJson(route('admin.emergencies.index'))->assertForbidden();
});

it('lists emergencies nobody has answered yet', function () {
    $location = pressEmergency($this->gobiker);
    $location->update(['designated_barangay' => 'Poblacion']);

    $emergencies = adminEmergencies($this->admin);

    expect($emergencies)->toHaveCount(1)
        ->and($emergencies[0])->toMatchArray([
            'location_id' => $location->id,
            'name' => 'Juan Dela Cruz',
            'mobile' => '09171234567',
            'barangay' => 'Poblacion',
        ])
        ->and($emergencies[0]['since'])->not->toBeNull();
});

it('stops listing an emergency once an admin is responding or resolved it', function () {
    $location = pressEmergency($this->gobiker);

    $this->actingAs($this->admin, 'web')
        ->patchJson(route('admin.locations.status', $location), ['status' => 'responding'])
        ->assertOk();
    expect(adminEmergencies($this->admin))->toBe([]);

    $this->patchJson(route('admin.locations.status', $location), ['status' => 'active'])->assertOk();
    expect(adminEmergencies($this->admin))->toBe([])
        ->and($location->fresh()->emergency_at)->toBeNull();
});

it('ignores locations that are not emergencies or not from GoBikers', function () {
    Location::create(['user_id' => $this->gobiker->id, 'name' => 'Active one', 'status' => 'active', 'last_seen_at' => now()]);
    $former = User::factory()->create(['role' => 'User']);
    Location::create(['user_id' => $former->id, 'name' => 'No longer a GoBiker', 'status' => 'emergency', 'emergency_at' => now()]);

    expect(adminEmergencies($this->admin))->toBe([]);
});

it('keeps the same alert when the GoBiker presses the button again while waiting', function () {
    pressEmergency($this->gobiker);
    $first = adminEmergencies($this->admin)[0]['key'];

    $this->travel(2)->minutes();
    pressEmergency($this->gobiker);

    expect(adminEmergencies($this->admin)[0]['key'])->toBe($first);
});

it('treats a new emergency after the admin was responding as a new alert', function () {
    $location = pressEmergency($this->gobiker);
    $first = adminEmergencies($this->admin)[0]['key'];

    $this->patchJson(route('admin.locations.status', $location), ['status' => 'responding'])->assertOk();

    $this->travel(2)->minutes();
    pressEmergency($this->gobiker);

    // A different key makes an alarm that was silenced for the first emergency sound again.
    expect(adminEmergencies($this->admin)[0]['key'])->not->toBe($first);
});

it('puts the emergency banner and alarm on every admin page', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.operations.news.index'))
        ->assertOk()
        ->assertSee('id="emergency-alert"', false)
        ->assertSee(route('admin.emergencies.index'), false)
        ->assertSee(asset('sounds/emergency-alarm.mp3'), false)
        ->assertSee('data-alarm-toggle', false);

    expect(public_path('sounds/emergency-alarm.mp3'))->toBeFile();
});

function emergencyBadge(User $admin): string
{
    $html = test()->actingAs($admin, 'web')->get(route('admin.operations.news.index'))->assertOk()->getContent();
    preg_match('/<span[^>]*data-nav-badge="emergencies"[^>]*>.*?<\/span>/s', $html, $badge);

    return $badge[0] ?? '';
}

it('shows the number of waiting emergencies next to Live map in the sidebar', function () {
    expect(emergencyBadge($this->admin))->toContain(' hidden');

    pressEmergency($this->gobiker);

    expect(emergencyBadge($this->admin))
        ->not->toContain(' hidden')
        ->toContain('<span class="count-num">1</span>');
});
