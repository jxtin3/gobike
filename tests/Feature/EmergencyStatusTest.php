<?php

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->gobiker = User::factory()->create(['role' => 'GoBiker', 'status' => 'Active']);
});

function sendEmergency(User $gobiker): Location
{
    Sanctum::actingAs($gobiker);
    test()->postJson(route('api.gobiker.emergency'), ['latitude' => 16.04, 'longitude' => 120.33])->assertOk();

    return Location::where('user_id', $gobiker->id)->sole();
}

it('has no emergency before one is sent', function () {
    Sanctum::actingAs($this->gobiker);

    $this->getJson(route('api.gobiker.emergency.status'))->assertOk()->assertExactJson(['status' => null]);
});

it('tells the GoBiker when the admin starts responding and when it is resolved', function () {
    $location = sendEmergency($this->gobiker);

    $this->getJson(route('api.gobiker.emergency.status'))->assertExactJson(['status' => 'emergency']);

    $this->actingAs($this->admin, 'web')
        ->patchJson(route('admin.locations.status', $location), ['status' => 'responding'])
        ->assertOk();

    Sanctum::actingAs($this->gobiker);
    $this->getJson(route('api.gobiker.emergency.status'))->assertExactJson(['status' => 'responding']);

    // A location update during the ronda must not hide the admin's response.
    $this->postJson(route('api.gobiker.location'), ['latitude' => 16.05, 'longitude' => 120.34])->assertOk();
    $this->getJson(route('api.gobiker.emergency.status'))->assertExactJson(['status' => 'responding']);

    $this->actingAs($this->admin, 'web')
        ->patchJson(route('admin.locations.status', $location), ['status' => 'active'])
        ->assertOk();

    Sanctum::actingAs($this->gobiker);
    $this->getJson(route('api.gobiker.emergency.status'))->assertExactJson(['status' => null]);
});

it('only shows a GoBiker their own emergency', function () {
    sendEmergency($this->gobiker);
    $other = User::factory()->create(['role' => 'GoBiker', 'status' => 'Active']);

    Sanctum::actingAs($other);

    $this->getJson(route('api.gobiker.emergency.status'))->assertExactJson(['status' => null]);
});

it('keeps the emergency open when the ronda is stopped', function () {
    sendEmergency($this->gobiker);
    $this->postJson(route('api.gobiker.active.stop'))->assertOk();

    $this->getJson(route('api.gobiker.emergency.status'))->assertExactJson(['status' => 'emergency']);
});

it('is only for signed-in GoBikers', function () {
    $this->getJson(route('api.gobiker.emergency.status'))->assertUnauthorized();

    Sanctum::actingAs(User::factory()->create(['role' => 'User']));
    $this->getJson(route('api.gobiker.emergency.status'))->assertForbidden();
});

it('shows the admin a plain "Responding" button on the live map', function () {
    expect(file_get_contents(resource_path('js/admin/live-map.js')))
        ->toContain('>Responding</button>')
        ->not->toContain("I'm responding");
});
