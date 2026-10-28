<?php

use App\Models\Appointment;
use App\Models\DeviceToken;
use App\Models\Location;
use App\Models\SosRequest;
use App\Models\User;
use App\Services\Push\FcmSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/** Records the messages instead of calling Firebase. */
class FakeFcmSender implements FcmSender
{
    /** @var list<array{token: string, title: string, body: string, data: array, channel: string}> */
    public array $sent = [];

    /** Tokens Firebase says are gone (the app was uninstalled). */
    public array $dead = [];

    public function enabled(): bool
    {
        return true;
    }

    public function send(string $token, string $title, string $body, array $data, string $channel): bool
    {
        $this->sent[] = compact('token', 'title', 'body', 'data', 'channel');

        return ! in_array($token, $this->dead, true);
    }
}

beforeEach(function () {
    $this->withoutDefer(); // send right away, inside the test
    $this->push = new FakeFcmSender;
    $this->app->instance(FcmSender::class, $this->push);

    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->resident = User::factory()->create([
        'name' => 'Ana Reyes', 'role' => 'User', 'status' => 'Active', 'barangay' => 'Poblacion', 'mobile' => '09170001111',
    ]);
    $this->gobiker = User::factory()->create([
        'name' => 'Maria Santos', 'role' => 'GoBiker', 'status' => 'Active', 'barangay' => 'Poblacion',
    ]);
    DeviceToken::create(['user_id' => $this->resident->id, 'token' => 'resident-phone']);
    DeviceToken::create(['user_id' => $this->gobiker->id, 'token' => 'gobiker-phone']);
});

function titlesFor(FakeFcmSender $push, string $token): array
{
    return collect($push->sent)->where('token', $token)->pluck('title')->all();
}

// ---------------------------------------------------------------- phones

it('registers a phone, moves it to the account that logs in, and removes it on logout', function () {
    Sanctum::actingAs($this->resident);
    $this->postJson(route('api.mobile.device-token.store'), ['token' => 'shared-phone'])->assertOk();

    Sanctum::actingAs($this->gobiker);
    $this->postJson(route('api.mobile.device-token.store'), ['token' => 'shared-phone'])->assertOk();
    expect(DeviceToken::where('token', 'shared-phone')->sole()->user_id)->toBe($this->gobiker->id);

    $this->deleteJson(route('api.mobile.device-token.destroy'), ['token' => 'shared-phone'])->assertOk();
    expect(DeviceToken::where('token', 'shared-phone')->exists())->toBeFalse();
});

it('forgets a phone Firebase says is gone', function () {
    $this->push->dead = ['resident-phone'];
    DeviceToken::where('token', 'resident-phone')->update(['updated_at' => now()->subHour()]);
    $sos = SosRequest::create(['user_id' => $this->resident->id, 'municipality' => 'Bugallon', 'barangay' => 'Poblacion', 'landmark' => 'Chapel']);

    $this->actingAs($this->admin, 'web')->patchJson(route('admin.operations.sos.status', $sos), ['status' => 'responding'])->assertOk();

    expect(DeviceToken::where('token', 'resident-phone')->exists())->toBeFalse();
});

it('keeps a phone that only just registered, even if Firebase is not ready for it yet', function () {
    $this->push->dead = ['resident-phone'];
    $sos = SosRequest::create(['user_id' => $this->resident->id, 'municipality' => 'Bugallon', 'barangay' => 'Poblacion', 'landmark' => 'Chapel']);

    $this->actingAs($this->admin, 'web')->patchJson(route('admin.operations.sos.status', $sos), ['status' => 'responding'])->assertOk();

    expect(DeviceToken::where('token', 'resident-phone')->exists())->toBeTrue();
});

it('marks a phone as just registered every time the app registers it', function () {
    DeviceToken::where('token', 'resident-phone')->update(['updated_at' => now()->subDay()]);

    Sanctum::actingAs($this->resident);
    $this->postJson('/api/mobile/device-token', ['token' => 'resident-phone'])->assertOk();

    expect(DeviceToken::where('token', 'resident-phone')->first()->updated_at->gt(now()->subMinute()))->toBeTrue();
});

it('sends to an FCM registration token or, for older app versions, an installation ID', function () {
    expect(\App\Services\Push\GoogleFcmSender::targetField('dQw4w9WgXcQ:APA91bHPRgkF3JUikC4ENAHEeMrd41Zxv3hVZjC9KtT8OvPVGJ-hQMRKRrZuJAEcl7B338qju59zJMjw2DELjzEvxwYv7hH5Ynpc1ODQ0aT4U4OFEeco8ohsN5PjL1iC2dNtk2BAokeMCg2ZXKqpc8FXKmhX94kIxQ'))->toBe('token')
        ->and(\App\Services\Push\GoogleFcmSender::targetField('cXyZ1234abcdEFGH5678ij'))->toBe('fid');
});

// ---------------------------------------------------------------- residents

it('tells the resident when the admin answers their SOS', function () {
    $sos = SosRequest::create(['user_id' => $this->resident->id, 'municipality' => 'Bugallon', 'barangay' => 'Poblacion', 'landmark' => 'Chapel']);
    $this->actingAs($this->admin, 'web');

    $this->patchJson(route('admin.operations.sos.status', $sos), ['status' => 'responding'])->assertOk();
    $this->patchJson(route('admin.operations.sos.status', $sos), ['status' => 'resolved'])->assertOk();

    expect(titlesFor($this->push, 'resident-phone'))->toBe(['Someone is Responding', 'SOS resolved'])
        ->and($this->push->sent[0]['data'])->toBe(['type' => 'sos', 'id' => (string) $sos->id])
        ->and($this->push->sent[0]['channel'])->toBe('rondago_alerts');
});

it('respects the resident\'s switches, including sound and vibration', function () {
    Sanctum::actingAs($this->resident);
    $this->putJson(route('api.mobile.notification-prefs'), ['sos_updates' => false, 'sound' => false])
        ->assertOk()
        ->assertJsonPath('notification_prefs.sos_updates', false)
        ->assertJsonPath('notification_prefs.appointments', true);

    $sos = SosRequest::create(['user_id' => $this->resident->id, 'municipality' => 'Bugallon', 'barangay' => 'Poblacion', 'landmark' => 'Chapel']);
    $this->actingAs($this->admin, 'web')->patchJson(route('admin.operations.sos.status', $sos), ['status' => 'responding']);
    expect(titlesFor($this->push, 'resident-phone'))->toBe([]);

    $visit = Appointment::create([
        'resident_id' => $this->resident->id, 'barangay' => 'Poblacion', 'address' => 'Purok 3',
        'preferred_date' => now()->addDay(), 'preferred_window' => 'morning', 'reason' => 'BP', 'status' => 'pending',
    ]);
    Sanctum::actingAs($this->gobiker);
    $this->postJson(route('api.gobiker.appointments.accept', $visit))->assertOk();

    $sent = collect($this->push->sent)->firstWhere('token', 'resident-phone');
    expect($sent['title'])->toBe('Home visit accepted')
        ->and($sent['body'])->toContain('Maria Santos will visit you')
        ->and($sent['channel'])->toBe('rondago_vibrate');
});

it('tells the resident when a GoBiker records their check-up', function () {
    Sanctum::actingAs($this->gobiker);
    $this->postJson(route('api.gobiker.patients.store'), [
        'name' => ' ana reyes', 'address' => 'Purok 3', 'contact' => '09170001111', 'age' => 60,
        'sys' => 130, 'dia' => 85, 'pulse' => 75, 'resp' => 16, 'temp' => 36.6,
    ])->assertCreated();

    expect(titlesFor($this->push, 'resident-phone'))->toBe(['New check-up recorded']);
});

// ---------------------------------------------------------------- GoBikers

it('tells GoBikers in the barangay about a new home-visit request', function () {
    $elsewhere = User::factory()->create(['role' => 'GoBiker', 'status' => 'Active', 'barangay' => 'Hacienda']);
    DeviceToken::create(['user_id' => $elsewhere->id, 'token' => 'elsewhere-phone']);

    Sanctum::actingAs($this->resident);
    $this->postJson(route('api.resident.appointments.store'), [
        'barangay' => 'Poblacion', 'address' => 'Purok 3', 'preferred_date' => now()->addDay()->toDateString(),
        'preferred_window' => 'morning', 'reason' => 'BP check',
    ])->assertCreated();

    expect(titlesFor($this->push, 'gobiker-phone'))->toBe(['New home-visit request'])
        ->and(titlesFor($this->push, 'elsewhere-phone'))->toBe([]);
});

it('tells only the chosen GoBiker about a request sent to them', function () {
    $chosen = User::factory()->create(['role' => 'GoBiker', 'status' => 'Active', 'barangay' => 'Hacienda']);
    DeviceToken::create(['user_id' => $chosen->id, 'token' => 'chosen-phone']);

    Sanctum::actingAs($this->resident);
    $this->postJson(route('api.resident.appointments.store'), [
        'barangay' => 'Poblacion', 'address' => 'Purok 3', 'preferred_date' => now()->addDay()->toDateString(),
        'preferred_window' => 'morning', 'reason' => 'BP check', 'gobiker_id' => $chosen->id,
    ])->assertCreated();

    expect(titlesFor($this->push, 'chosen-phone'))->toBe(['Home-visit request for you'])
        ->and(titlesFor($this->push, 'gobiker-phone'))->toBe([]);
});

it('tells the GoBiker when the admin answers their emergency', function () {
    $location = Location::create(['user_id' => $this->gobiker->id, 'name' => 'Maria', 'latitude' => 15.95, 'longitude' => 120.18, 'status' => 'emergency', 'emergency_at' => now()]);

    $this->actingAs($this->admin, 'web')->patchJson(route('admin.locations.status', $location), ['status' => 'responding'])->assertOk();

    expect(titlesFor($this->push, 'gobiker-phone'))->toBe(['The admin saw your alert']);
});

// ---------------------------------------------------------------- Google accounts

it('lets a Google account set a password without a current one, then requires it', function () {
    $google = User::factory()->create(['role' => 'User', 'status' => 'Active', 'google_id' => 'g-1', 'has_password' => false]);
    Sanctum::actingAs($google);

    $this->getJson('/api/mobile/me')->assertJsonPath('user.has_password', false);

    $this->putJson(route('api.mobile.password'), ['password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass'])
        ->assertOk()
        ->assertJsonPath('user.has_password', true);
    expect(Hash::check('brand-new-pass', $google->fresh()->password))->toBeTrue();

    $this->putJson(route('api.mobile.password'), ['password' => 'another-pass1', 'password_confirmation' => 'another-pass1'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('current_password');
});
