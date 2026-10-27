<?php

use App\Models\SosRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->resident = User::factory()->create([
        'name' => 'Ana Reyes', 'role' => 'User', 'status' => 'Active', 'mobile' => '09170001111',
        'barangay' => 'Poblacion',
    ]);
    $this->admin = User::factory()->create(['is_admin' => true]);
});

function sosPayload(array $overrides = []): array
{
    return array_merge([
        'address' => '12 Rizal St., Purok 2',
        'landmark' => 'Beside the chapel',
        'description' => 'My father fell and cannot stand.',
        'latitude' => 15.9566,
        'longitude' => 120.1866,
    ], $overrides);
}

function sendSos(User $resident, array $overrides = []): SosRequest
{
    Sanctum::actingAs($resident);
    $id = test()->postJson(route('api.resident.sos.store'), sosPayload($overrides))->assertCreated()->json('sos.id');

    return SosRequest::findOrFail($id);
}

// ---------------------------------------------------------------- resident API

it('sends an SOS that waits for a response', function () {
    $sos = sendSos($this->resident);

    expect($sos->status)->toBe('sent')
        ->and($sos->user_id)->toBe($this->resident->id)
        ->and($sos->latitude)->toBe(15.9566);
});

it('sends an SOS without a GPS position', function () {
    $sos = sendSos($this->resident, ['latitude' => null, 'longitude' => null]);

    expect($sos->map_url)->toBeNull();
});

it('needs the address and landmark of the emergency', function (string $field) {
    Sanctum::actingAs($this->resident);

    $this->postJson(route('api.resident.sos.store'), sosPayload([$field => '']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with(['address', 'landmark']);

it('files the SOS under the resident\'s own barangay', function () {
    $sos = sendSos($this->resident);

    expect($sos->barangay)->toBe('Poblacion')
        ->and($sos->municipality)->toBe('Bugallon')
        ->and($sos->address)->toBe('12 Rizal St., Purok 2');
});

it('still accepts an SOS from older app versions that pick a barangay', function () {
    $sos = sendSos($this->resident, ['address' => null, 'municipality' => 'Bugallon', 'barangay' => 'Hacienda']);

    expect($sos->barangay)->toBe('Hacienda')->and($sos->address)->toBeNull();
});

it('asks a resident without a barangay to set one first', function () {
    $this->resident->update(['barangay' => null]);
    Sanctum::actingAs($this->resident);

    $this->postJson(route('api.resident.sos.store'), sosPayload())
        ->assertUnprocessable()
        ->assertJsonValidationErrors('address');
});

it('limits how many SOS requests a resident can send per minute', function () {
    Sanctum::actingAs($this->resident);

    foreach (range(1, 3) as $i) {
        $this->postJson(route('api.resident.sos.store'), sosPayload())->assertCreated();
    }
    $this->postJson(route('api.resident.sos.store'), sosPayload())->assertTooManyRequests();
});

it('attaches a photo and a video to the resident\'s own request', function () {
    $sos = sendSos($this->resident);

    $this->post(route('api.resident.sos.media', $sos), [
        'image' => UploadedFile::fake()->image('scene.jpg'),
        'video' => UploadedFile::fake()->create('scene.mp4', 2048, 'video/mp4'),
    ], ['Accept' => 'application/json'])->assertOk()
        ->assertJsonPath('sos.image_url', fn ($url) => str_contains($url, '/storage/sos/'));

    $sos->refresh();
    Storage::disk('public')->assertExists([$sos->image_path, $sos->video_path]);
});

it('rejects media that is not a photo or video, or is too big', function (string $field, UploadedFile $file) {
    $sos = sendSos($this->resident);

    $this->post(route('api.resident.sos.media', $sos), [$field => $file], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'pdf as photo' => fn () => ['image', UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')],
    'huge photo' => fn () => ['image', UploadedFile::fake()->image('a.jpg')->size(6000)],
    'huge video' => fn () => ['video', UploadedFile::fake()->create('a.mp4', 52000, 'video/mp4')],
]);

it('does not let anyone add media to another resident\'s request', function () {
    $sos = sendSos($this->resident);
    Sanctum::actingAs(User::factory()->create(['role' => 'User', 'status' => 'Active']));

    $this->post(route('api.resident.sos.media', $sos), ['image' => UploadedFile::fake()->image('x.jpg')], ['Accept' => 'application/json'])
        ->assertForbidden();
});

it('lists only the resident\'s own requests with their status', function () {
    sendSos($this->resident);
    sendSos(User::factory()->create(['role' => 'User', 'status' => 'Active', 'barangay' => 'Hacienda']));

    Sanctum::actingAs($this->resident);
    $this->getJson(route('api.resident.sos.index'))
        ->assertOk()
        ->assertJsonCount(1, 'sos')
        ->assertJsonPath('sos.0.status', 'sent')
        ->assertJsonPath('sos.0.status_label', 'Waiting for response');
});

it('is only for residents', function () {
    Sanctum::actingAs(User::factory()->create(['role' => 'GoBiker', 'status' => 'Active']));

    $this->postJson(route('api.resident.sos.store'), sosPayload())->assertForbidden();
});

// ---------------------------------------------------------------- admin

it('rings the admin alarm until an admin answers', function () {
    $sos = sendSos($this->resident);

    $alarm = $this->actingAs($this->admin, 'web')->getJson(route('admin.emergencies.index'))->assertOk()->json('emergencies');
    expect($alarm)->toHaveCount(1)
        ->and($alarm[0]['kind'])->toBe('resident')
        ->and($alarm[0]['name'])->toBe('Ana Reyes')
        ->and($alarm[0]['respond_url'])->toBe(route('admin.operations.sos.status', $sos));

    $this->patchJson(route('admin.operations.sos.status', $sos), ['status' => 'responding'])->assertOk();

    expect($this->getJson(route('admin.emergencies.index'))->json('emergencies'))->toBe([])
        ->and($sos->fresh()->handled_by)->toBe($this->admin->id);

    Sanctum::actingAs($this->resident);
    $this->getJson(route('api.resident.sos.index'))->assertJsonPath('sos.0.status', 'responding');
});

it('never moves a request backwards', function () {
    $sos = sendSos($this->resident);
    $this->actingAs($this->admin, 'web');

    $this->patchJson(route('admin.operations.sos.status', $sos), ['status' => 'resolved'])->assertOk();
    $this->patchJson(route('admin.operations.sos.status', $sos), ['status' => 'responding'])->assertUnprocessable();

    expect($sos->fresh())
        ->status->toBe('resolved')
        ->responded_at->not->toBeNull()
        ->resolved_at->not->toBeNull();
});

it('shows the requests and their details to admins only', function () {
    $sos = sendSos($this->resident, ['landmark' => 'Near the old acacia tree']);

    $this->actingAs($this->resident, 'web')->get(route('admin.operations.sos.index'))->assertForbidden();

    $this->actingAs($this->admin, 'web')
        ->get(route('admin.operations.sos.index'))
        ->assertOk()
        ->assertSee('Ana Reyes')
        ->assertSee('Waiting for response');

    $this->get(route('admin.operations.sos.show', $sos))
        ->assertOk()
        ->assertSee('Near the old acacia tree')
        ->assertSee('Open in Google Maps')
        ->assertSee('Responding');
});

it('counts waiting requests in the sidebar', function () {
    sendSos($this->resident);

    $this->actingAs($this->admin, 'web')
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('data-nav-badge="sos"', false)
        ->assertSee('SOS requests waiting for a response');
});

// ---------------------------------------------------------------- resident resolves

it('lets the resident mark their own SOS as resolved, which stops the alarm', function () {
    $sos = sendSos($this->resident);

    $this->postJson(route('api.resident.sos.resolve', $sos))
        ->assertOk()
        ->assertJsonPath('sos.status', 'resolved')
        ->assertJsonPath('sos.resolved_by_resident', true);

    expect($sos->fresh()->resolved_at)->not->toBeNull();

    $this->actingAs($this->admin, 'web')
        ->getJson(route('admin.emergencies.index'))
        ->assertJsonPath('emergencies', []);
    $this->get(route('admin.operations.sos.show', $sos))->assertSee('the resident marked it resolved');
});

it('lets the resident resolve while the admin is responding, but not twice', function () {
    $sos = sendSos($this->resident);
    $sos->update(['status' => 'responding']);

    $this->postJson(route('api.resident.sos.resolve', $sos))->assertOk();
    $this->postJson(route('api.resident.sos.resolve', $sos))->assertUnprocessable();
});

it('does not let anyone resolve another resident\'s SOS', function () {
    $sos = sendSos($this->resident);
    Sanctum::actingAs(User::factory()->create(['role' => 'User', 'status' => 'Active']));

    $this->postJson(route('api.resident.sos.resolve', $sos))->assertForbidden();
    expect($sos->fresh()->status)->toBe('sent');
});

// ---------------------------------------------------------------- clear history

it('clears finished SOS requests and visits from the resident history, keeping open ones and the admin records', function () {
    $done = sendSos($this->resident);
    $done->update(['status' => 'resolved', 'resolved_at' => now()]);
    $open = sendSos($this->resident, ['landmark' => 'Still waiting']);
    $closedVisit = \App\Models\Appointment::create([
        'resident_id' => $this->resident->id, 'barangay' => 'Poblacion', 'address' => 'Purok 3',
        'preferred_date' => now()->addDay(), 'preferred_window' => 'morning', 'reason' => 'BP', 'status' => 'cancelled',
    ]);

    $this->postJson(route('api.resident.history.clear'))
        ->assertOk()
        ->assertJsonPath('cleared', ['sos' => 1, 'appointments' => 1]);

    $this->getJson(route('api.resident.sos.index'))
        ->assertJsonCount(1, 'sos')
        ->assertJsonPath('sos.0.id', $open->id);
    $this->getJson(route('api.resident.appointments.index'))->assertJsonCount(0, 'appointments');

    // The admin still has everything.
    expect(SosRequest::count())->toBe(2)
        ->and(\App\Models\Appointment::find($closedVisit->id))->not->toBeNull();
    $this->actingAs($this->admin, 'web')->get(route('admin.operations.sos.show', $done))->assertOk();
});
