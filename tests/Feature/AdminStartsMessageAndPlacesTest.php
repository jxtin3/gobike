<?php

use App\Models\Barangay;
use App\Models\City;
use App\Models\GobikerMessage;
use App\Models\Location;
use App\Models\Municipality;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** Pangasinan → Pangasinan → Bugallon → Salasa, linked the way the location tables are. */
function salasaInBugallon(): void
{
    $province = Province::create(['name' => 'Pangasinan', 'region' => 'Region I']);
    $city = City::create(['province_id' => $province->id, 'name' => 'Pangasinan']);
    $bugallon = Municipality::create(['city_id' => $city->id, 'name' => 'Bugallon']);
    // The migrations already seed the barangays: link the one we use to its town.
    Barangay::updateOrCreate(['name' => 'Salasa'], ['municipality' => 'Bugallon', 'municipality_id' => $bugallon->id, 'city_id' => $city->id, 'province_id' => $province->id]);
}

it('lets the admin start a message to a GoBiker who never wrote', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $gobiker = User::factory()->create(['role' => 'GoBiker', 'status' => 'Active', 'name' => 'Maria Cruz']);

    $this->actingAs($admin)
        ->postJson(route('admin.operations.gobiker-messages.store'), [
            'user_id' => $gobiker->id,
            'message' => 'Please check in at the barangay hall tomorrow.',
        ])
        ->assertCreated()
        ->assertJson(['success' => true]);

    $row = GobikerMessage::sole();
    expect($row->from_admin)->toBeTrue()
        ->and($row->is_read)->toBeTrue()
        ->and($row->reply_from_admin_id)->toBe($admin->id);

    // Nothing in it waits for the admin.
    $this->actingAs($admin)
        ->get(route('admin.operations.gobiker-messages.index'))
        ->assertOk()
        ->assertSee('data-unread-count>0<', false);

    // The GoBiker's app gets it, marked as the admin's.
    $this->actingAs($gobiker, 'sanctum')
        ->getJson('/api/gobiker/messages')
        ->assertOk()
        ->assertJsonFragment([
            'message' => 'Please check in at the barangay hall tomorrow.',
            'from_admin' => true,
        ]);
});

it('only lets the admin write to an active GoBiker', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $resident = User::factory()->create(['role' => 'User']);

    $this->actingAs($admin)
        ->postJson(route('admin.operations.gobiker-messages.store'), [
            'user_id' => $resident->id,
            'message' => 'Hello',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['user_id' => 'Choose an active GoBiker.']);

    expect(GobikerMessage::count())->toBe(0);
});

it('sends the place names back so Edit profile can show them again', function () {
    salasaInBugallon();
    $gobiker = User::factory()->create(['role' => 'GoBiker', 'mobile' => '09987654321', 'barangay' => 'Angarian']);

    $this->actingAs($gobiker, 'sanctum')
        ->putJson('/api/gobiker/profile', ['mobile' => '09987654321', 'barangay' => 'Salasa'])
        ->assertOk()
        ->assertJsonPath('user.barangay', 'Salasa')
        ->assertJsonPath('user.municipality', 'Bugallon')
        ->assertJsonPath('user.city', 'Pangasinan')
        ->assertJsonPath('user.province', 'Pangasinan');
});

it('shows the profile barangay and town on the live map', function () {
    salasaInBugallon();
    $admin = User::factory()->create(['is_admin' => true]);
    $gobiker = User::factory()->create(['role' => 'GoBiker', 'name' => 'Maria Cruz', 'barangay' => 'Salasa']);
    Location::create(['user_id' => $gobiker->id, 'latitude' => 16.0, 'longitude' => 120.3, 'status' => 'active', 'last_seen_at' => now()]);

    $this->actingAs($admin)
        ->getJson(route('admin.locations.index'))
        ->assertOk()
        ->assertJsonPath('0.designated_barangay', 'Salasa')
        ->assertJsonPath('0.municipality', 'Bugallon');
});

it('moves the live-map barangay with the profile, but not one a ronda set', function () {
    salasaInBugallon();
    Barangay::firstOrCreate(['name' => 'Angarian'], ['municipality' => 'Bugallon']);
    Barangay::firstOrCreate(['name' => 'Laguit'], ['municipality' => 'Bugallon']);

    $follows = User::factory()->create(['role' => 'GoBiker', 'mobile' => '09111111111', 'barangay' => 'Angarian']);
    $onRonda = User::factory()->create(['role' => 'GoBiker', 'mobile' => '09222222222', 'barangay' => 'Angarian']);
    Location::create(['user_id' => $follows->id, 'designated_barangay' => 'Angarian', 'status' => 'active']);
    Location::create(['user_id' => $onRonda->id, 'designated_barangay' => 'Laguit', 'status' => 'active']);

    $this->actingAs($follows, 'sanctum')
        ->putJson('/api/gobiker/profile', ['mobile' => '09111111111', 'barangay' => 'Salasa'])->assertOk();
    $this->actingAs($onRonda, 'sanctum')
        ->putJson('/api/gobiker/profile', ['mobile' => '09222222222', 'barangay' => 'Salasa'])->assertOk();

    expect(Location::where('user_id', $follows->id)->value('designated_barangay'))->toBe('Salasa')
        ->and(Location::where('user_id', $onRonda->id)->value('designated_barangay'))->toBe('Laguit');
});

it('offers New message on the page and labels the admin-started rows', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $gobiker = User::factory()->create(['role' => 'GoBiker', 'status' => 'Active', 'name' => 'Maria Cruz']);
    GobikerMessage::create(['user_id' => $gobiker->id, 'message' => 'Check in tomorrow.', 'from_admin' => true, 'is_read' => true]);

    $this->actingAs($admin)
        ->get(route('admin.operations.gobiker-messages.index'))
        ->assertOk()
        ->assertSee('New message')
        ->assertSee('id="newGobikerMessageDialog"', false)
        ->assertSee('<option value="'.$gobiker->id.'">Maria Cruz', false)
        ->assertSee('From admin → Maria Cruz');
});

it('does not let the admin reply to their own message', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $gobiker = User::factory()->create(['role' => 'GoBiker', 'status' => 'Active']);
    $row = GobikerMessage::create(['user_id' => $gobiker->id, 'message' => 'Check in tomorrow.', 'from_admin' => true, 'is_read' => true]);

    $this->actingAs($admin)
        ->postJson(route('admin.operations.gobiker-messages.reply', $row), ['reply_message' => 'Again'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('reply_message');

    expect($row->fresh()->reply_message)->toBeNull();
});
