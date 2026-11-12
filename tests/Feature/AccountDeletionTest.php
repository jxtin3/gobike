<?php

use App\Models\Appointment;
use App\Models\DeviceToken;
use App\Models\Location;
use App\Models\Patient;
use App\Models\SosRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    Storage::fake(SosRequest::mediaDisk());

    $this->resident = User::factory()->create([
        'name' => 'Ana Reyes', 'role' => 'User', 'status' => 'Active', 'barangay' => 'Poblacion',
        'mobile' => '09170001111', 'photo_path' => 'profile-photos/ana.jpg',
    ]);
    Storage::disk('public')->put('profile-photos/ana.jpg', 'photo');
});

function recordCheckup(User $gobiker, array $overrides = []): Patient
{
    return Patient::create($overrides + [
        'user_id' => $gobiker->id, 'barangay' => 'Poblacion', 'name' => 'Ana Reyes', 'address' => 'Purok 2',
        'contact' => '09170001111', 'age' => 61, 'sys' => 120, 'dia' => 80, 'pulse' => 70, 'resp' => 16,
        'temp' => 36.5, 'recorded_at' => now(),
    ]);
}

it('lets a resident delete their account and everything that belongs to it', function () {
    $gobiker = User::factory()->create(['role' => 'GoBiker', 'status' => 'Active', 'barangay' => 'Poblacion']);
    $checkup = recordCheckup($gobiker);
    expect($checkup->fresh()->resident_id)->toBe($this->resident->id);

    DeviceToken::create(['user_id' => $this->resident->id, 'token' => 'ana-phone']);
    $sos = SosRequest::create([
        'user_id' => $this->resident->id, 'barangay' => 'Poblacion', 'municipality' => 'Bugallon',
        'address' => 'Purok 2', 'landmark' => 'Chapel', 'status' => 'resolved',
        'image_path' => 'sos/scene.jpg', 'video_path' => 'sos/scene.mp4',
    ]);
    Storage::disk(SosRequest::mediaDisk())->put('sos/scene.jpg', 'p');
    Storage::disk(SosRequest::mediaDisk())->put('sos/scene.mp4', 'v');
    Appointment::create([
        'resident_id' => $this->resident->id, 'gobiker_id' => $gobiker->id, 'barangay' => 'Poblacion',
        'address' => 'Purok 2', 'preferred_date' => now()->addDay()->toDateString(), 'preferred_window' => 'morning',
        'reason' => 'BP check', 'status' => 'pending',
    ]);

    Sanctum::actingAs($this->resident);
    $this->deleteJson(route('api.mobile.account.destroy'), ['password' => 'password'])
        ->assertOk()
        ->assertJsonPath('message', 'Your account has been deleted.');

    expect(User::find($this->resident->id))->toBeNull()
        ->and(DeviceToken::where('user_id', $this->resident->id)->exists())->toBeFalse()
        ->and(SosRequest::find($sos->id))->toBeNull()
        ->and(Appointment::where('resident_id', $this->resident->id)->exists())->toBeFalse();
    Storage::disk('public')->assertMissing('profile-photos/ana.jpg');
    Storage::disk(SosRequest::mediaDisk())->assertMissing(['sos/scene.jpg', 'sos/scene.mp4']);

    // The check-up about her is health information the program keeps, no longer linked to her.
    expect($checkup->fresh())->not->toBeNull()
        ->and($checkup->fresh()->resident_id)->toBeNull();
});

it('needs the right password', function () {
    Sanctum::actingAs($this->resident);

    $this->deleteJson(route('api.mobile.account.destroy'), ['password' => 'wrong-password'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password' => 'The password is incorrect.']);

    expect(User::find($this->resident->id))->not->toBeNull();
});

it('asks an account without a password (Google sign-in) to type DELETE', function () {
    $this->resident->update(['has_password' => false]);
    Sanctum::actingAs($this->resident->fresh());

    $this->deleteJson(route('api.mobile.account.destroy'), ['confirm' => 'delete'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['confirm' => 'Type DELETE to confirm.']);

    $this->deleteJson(route('api.mobile.account.destroy'), ['confirm' => 'DELETE'])->assertOk();

    expect(User::find($this->resident->id))->toBeNull();
});

it('lets a GoBiker without check-ups delete their account and their place on the map', function () {
    $gobiker = User::factory()->create(['name' => 'Maria Santos', 'role' => 'GoBiker', 'status' => 'Active', 'barangay' => 'Poblacion']);
    Location::create(['user_id' => $gobiker->id, 'name' => 'Maria Santos', 'latitude' => 15.95, 'longitude' => 120.18, 'status' => 'Active', 'last_seen_at' => now()]);

    Sanctum::actingAs($gobiker);
    $this->deleteJson(route('api.mobile.account.destroy'), ['password' => 'password'])->assertOk();

    expect(User::find($gobiker->id))->toBeNull()
        ->and(Location::where('name', 'Maria Santos')->exists())->toBeFalse();
});

it('keeps the account of someone who recorded check-ups, so the records are not lost', function () {
    $gobiker = User::factory()->create(['role' => 'GoBiker', 'status' => 'Active', 'barangay' => 'Poblacion']);
    recordCheckup($gobiker);

    Sanctum::actingAs($gobiker);
    $this->deleteJson(route('api.mobile.account.destroy'), ['password' => 'password'])
        ->assertStatus(409)
        ->assertJsonPath('message', fn ($message) => str_contains($message, 'gobike@pydn.org'));

    expect(User::find($gobiker->id))->not->toBeNull()
        ->and(Patient::count())->toBe(1);
});

it('does not delete admin accounts from the app', function () {
    $admin = User::factory()->create(['is_admin' => true, 'role' => 'Admin']);
    Sanctum::actingAs($admin);

    $this->deleteJson(route('api.mobile.account.destroy'), ['password' => 'password'])->assertForbidden();

    expect(User::find($admin->id))->not->toBeNull();
});

it('needs a signed-in user', function () {
    $this->deleteJson(route('api.mobile.account.destroy'), ['password' => 'password'])->assertUnauthorized();
});
