<?php

use App\Models\Appointment;
use App\Models\SosRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    Storage::fake(SosRequest::mediaDisk());
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
        ->assertJsonPath('sos.image_url', route('admin.operations.sos.media', [$sos, 'image']))
        ->assertJsonPath('sos.video_url', route('admin.operations.sos.media', [$sos, 'video']));

    $sos->refresh();
    Storage::disk(SosRequest::mediaDisk())->assertExists([$sos->image_path, $sos->video_path]);
    Storage::disk('public')->assertMissing([$sos->image_path, $sos->video_path]);
});

it('opens the photo and video for admins only', function () {
    $sos = sendSos($this->resident);
    $this->post(route('api.resident.sos.media', $sos), [
        'image' => UploadedFile::fake()->image('scene.jpg'),
        'video' => UploadedFile::fake()->create('scene.mp4', 256, 'video/mp4'),
    ], ['Accept' => 'application/json'])->assertOk();

    // A visitor who is not signed in (Sanctum::actingAs switched the default guard).
    auth()->shouldUse('web');
    auth()->forgetGuards();
    $this->get(route('admin.operations.sos.media', [$sos, 'image']))->assertRedirect();
    $this->actingAs($this->resident, 'web')->get(route('admin.operations.sos.media', [$sos, 'image']))->assertForbidden();

    $this->actingAs($this->admin, 'web');
    foreach (['image', 'video'] as $kind) {
        $response = $this->get(route('admin.operations.sos.media', [$sos, $kind]))->assertOk();
        expect($response->headers->get('Cache-Control'))->toContain('no-store');
    }

    $this->get(route('admin.operations.sos.show', $sos))
        ->assertOk()
        ->assertSee('src="'.route('admin.operations.sos.media', [$sos, 'image']).'"', false)
        ->assertDontSee('/storage/sos/');
});

it('replaces an earlier photo and removes the old file', function () {
    $sos = sendSos($this->resident);
    $this->post(route('api.resident.sos.media', $sos), ['image' => UploadedFile::fake()->image('a.jpg')], ['Accept' => 'application/json'])->assertOk();
    $first = $sos->fresh()->image_path;

    $this->post(route('api.resident.sos.media', $sos), ['image' => UploadedFile::fake()->image('b.jpg')], ['Accept' => 'application/json'])->assertOk();

    Storage::disk(SosRequest::mediaDisk())->assertMissing($first);
    Storage::disk(SosRequest::mediaDisk())->assertExists($sos->fresh()->image_path);
});

it('still shows a photo saved on the public disk before media became private', function () {
    $sos = sendSos($this->resident);
    Storage::disk('public')->put('sos/old.jpg', 'old photo');
    $sos->update(['image_path' => 'sos/old.jpg']);

    $this->actingAs($this->admin, 'web')
        ->get(route('admin.operations.sos.media', [$sos, 'image']))
        ->assertOk();
});

it('moves photos and videos from the public disk to the private disk', function () {
    $sos = sendSos($this->resident);
    Storage::disk('public')->put('sos/old.jpg', 'old photo');
    Storage::disk('public')->put('sos/old.mp4', 'old video');
    $sos->update(['image_path' => 'sos/old.jpg', 'video_path' => 'sos/old.mp4']);

    $migration = require database_path('migrations/2026_11_13_000002_move_sos_media_to_private_disk.php');
    $migration->up();

    Storage::disk(SosRequest::mediaDisk())->assertExists(['sos/old.jpg', 'sos/old.mp4']);
    Storage::disk('public')->assertMissing(['sos/old.jpg', 'sos/old.mp4']);
    expect(Storage::disk(SosRequest::mediaDisk())->get('sos/old.jpg'))->toBe('old photo');
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

it('lets an admin delete a request with its photo and video', function () {
    $sos = sendSos($this->resident);
    $this->post(route('api.resident.sos.media', $sos), [
        'image' => UploadedFile::fake()->image('scene.jpg'),
        'video' => UploadedFile::fake()->create('scene.mp4', 512, 'video/mp4'),
    ], ['Accept' => 'application/json'])->assertOk();
    $files = [$sos->fresh()->image_path, $sos->fresh()->video_path];

    $this->actingAs($this->resident, 'web')->delete(route('admin.operations.sos.destroy', $sos))->assertForbidden();
    $this->actingAs($this->admin, 'web')
        ->delete(route('admin.operations.sos.destroy', $sos))
        ->assertRedirect(route('admin.operations.sos.index'));

    expect(SosRequest::find($sos->id))->toBeNull();
    Storage::disk(SosRequest::mediaDisk())->assertMissing($files);
});

it('opens a request by clicking anywhere on its row', function () {
    $sos = sendSos($this->resident);

    $this->actingAs($this->admin, 'web')
        ->get(route('admin.operations.sos.index', ['status' => 'all']))
        ->assertSee('data-href="'.route('admin.operations.sos.show', $sos).'"', false);
});

it('counts waiting requests in the sidebar', function () {
    sendSos($this->resident);

    $this->actingAs($this->admin, 'web')
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('data-nav-badge="sos"', false)
        ->assertSee('SOS requests waiting for a response');
});

// ---------------------------------------------------------- nobody but a responder resolves

it('does not let a resident close their own emergency, whatever its status', function (string $status) {
    // An emergency stays open until the GoBiker sent to help, or an admin, ends it.
    $sos = sendSos($this->resident);
    $sos->update(['status' => $status]);

    $this->postJson(route('api.resident.sos.resolve', $sos))
        ->assertForbidden()
        ->assertJsonPath('message', 'Only the GoBiker sent to help, or the Go Bike admin, can close an emergency request.');

    expect($sos->fresh())->status->toBe($status)->resolved_at->toBeNull();
})->with(['sent', 'responding']);

it('does not let anyone resolve another resident\'s SOS', function () {
    $sos = sendSos($this->resident);
    Sanctum::actingAs(User::factory()->create(['role' => 'User', 'status' => 'Active']));

    $this->postJson(route('api.resident.sos.resolve', $sos))->assertForbidden();
    expect($sos->fresh()->status)->toBe('sent');
});

it('still shows an older request the resident closed themselves', function () {
    // Residents could close their own emergency once; those rows must keep reading that way.
    $sos = sendSos($this->resident);
    $sos->update(['status' => 'resolved', 'resolved_at' => now(), 'resolved_by_resident' => true]);

    $this->actingAs($this->admin, 'web')
        ->get(route('admin.operations.sos.show', $sos))
        ->assertSee('the resident marked it resolved');

    Sanctum::actingAs($this->resident);
    $this->getJson(route('api.resident.sos.index'))->assertJsonPath('sos.0.resolved_by_resident', true);
});

// ---------------------------------------------------------------- cancelling

it('lets a resident cancel their own open SOS, and keeps the record', function (string $status) {
    $sos = sendSos($this->resident);
    $sos->update(['status' => $status]);

    $this->postJson(route('api.resident.sos.cancel', $sos))
        ->assertOk()
        ->assertJsonPath('message', 'SOS cancelled.')
        ->assertJsonPath('sos.cancelled', true)
        ->assertJsonPath('sos.status_label', 'Cancelled')
        // Apps from before "cancelled" existed read anything but "resolved" as still open.
        ->assertJsonPath('sos.status', 'resolved');

    expect($sos->fresh())
        ->status->toBe('cancelled')
        ->resolved_by_resident->toBeTrue()
        ->resolved_at->not->toBeNull()
        ->resolved_by_gobiker_id->toBeNull();
    expect(SosRequest::open()->count())->toBe(0);
})->with(['sent', 'responding']);

it('refuses to cancel an SOS that is already closed', function (string $status) {
    $sos = sendSos($this->resident);
    $sos->update(['status' => $status, 'resolved_at' => now()]);

    $this->postJson(route('api.resident.sos.cancel', $sos))
        ->assertUnprocessable()
        ->assertJsonPath('message', 'This request is already closed.');

    expect($sos->fresh()->status)->toBe($status);
})->with(['resolved', 'cancelled']);

it("does not let anyone cancel another resident's SOS", function () {
    $sos = sendSos($this->resident);
    Sanctum::actingAs(User::factory()->create(['role' => 'User', 'status' => 'Active']));

    $this->postJson(route('api.resident.sos.cancel', $sos))->assertForbidden();
    expect($sos->fresh()->status)->toBe('sent');
});

it('takes a cancelled SOS off the live map and the alarm, and shows it to the admin', function () {
    $sos = sendSos($this->resident);
    $this->postJson(route('api.resident.sos.cancel', $sos))->assertOk();

    $this->actingAs($this->admin, 'web')
        ->getJson(route('admin.sos-pins.index'))
        ->assertOk()
        ->assertJsonCount(0);
    $this->actingAs($this->admin, 'web')
        ->getJson(route('admin.emergencies.index'))
        ->assertOk()
        ->assertJsonMissing(['sos_id' => $sos->id]);
    $this->actingAs($this->admin, 'web')
        ->get(route('admin.operations.sos.index', ['status' => 'cancelled']))
        ->assertOk()
        ->assertSee('Ana Reyes');
    $this->actingAs($this->admin, 'web')
        ->get(route('admin.operations.sos.show', $sos))
        ->assertSee('the resident cancelled it');
});

it('does not let an admin move a cancelled SOS back', function () {
    $sos = sendSos($this->resident);
    $this->postJson(route('api.resident.sos.cancel', $sos))->assertOk();

    $this->actingAs($this->admin, 'web')
        ->patchJson(route('admin.operations.sos.status', $sos), ['status' => 'responding'])
        ->assertUnprocessable();
    expect($sos->fresh()->status)->toBe('cancelled');
});

it('clears a cancelled SOS from the resident history', function () {
    $sos = sendSos($this->resident);
    $this->postJson(route('api.resident.sos.cancel', $sos))->assertOk();

    $this->postJson(route('api.resident.history.clear'))->assertOk();
    $this->getJson(route('api.resident.sos.index'))->assertJsonCount(0, 'sos');
});

// ---------------------------------------------------------------- clear history

it('clears finished SOS requests and visits from the resident history, keeping open ones and the admin records', function () {
    $done = sendSos($this->resident);
    $done->update(['status' => 'resolved', 'resolved_at' => now()]);
    $open = sendSos($this->resident, ['landmark' => 'Still waiting']);
    $closedVisit = Appointment::create([
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
        ->and(Appointment::find($closedVisit->id))->not->toBeNull();
    $this->actingAs($this->admin, 'web')->get(route('admin.operations.sos.show', $done))->assertOk();
});
