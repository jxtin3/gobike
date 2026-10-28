<?php

use App\Models\Appointment;
use App\Models\ContactMessage;
use App\Models\Donation;
use App\Models\GobikerMessage;
use App\Models\Location;
use App\Models\Patient;
use App\Models\PatientFollowUp;
use App\Models\ReplicationRequest;
use App\Models\SosRequest;
use App\Models\TrainingRequest;
use App\Models\User;
use App\Models\VolunteerApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('fills every admin section with 50+ records covering each status', function () {
    $this->artisan('demo:seed', ['--force' => true])->assertSuccessful();

    expect(User::where('role', 'GoBiker')->count())->toBe(30)
        ->and(User::pendingApproval()->count())->toBe(6)
        ->and(User::where('role', 'User')->count())->toBe(45)
        ->and(Patient::count())->toBeGreaterThanOrEqual(50)
        ->and(GobikerMessage::count())->toBeGreaterThanOrEqual(50)
        ->and(GobikerMessage::where('is_read', false)->exists())->toBeTrue()
        // Nothing that rings the admin alarm during a presentation.
        ->and(Location::whereIn('status', Location::ALERT_STATUSES)->count())->toBe(0)
        ->and(SosRequest::where('status', 'sent')->count())->toBe(0);

    foreach ([
        [Appointment::class, 'status', array_keys(Appointment::STATUSES)],
        [SosRequest::class, 'status', ['responding', 'resolved']],
        [Donation::class, 'status', ['paid', 'pending', 'failed', 'cancelled']],
        [Donation::class, 'payment_method', ['paypal', 'gcash', 'bank']],
        [Donation::class, 'frequency', ['once', 'monthly']],
        [ContactMessage::class, 'is_read', [true, false]],
        [TrainingRequest::class, 'tier', array_keys(TrainingRequest::TIERS)],
        [TrainingRequest::class, 'status', TrainingRequest::STATUSES],
        [VolunteerApplication::class, 'application_status', VolunteerApplication::STATUSES],
        [ReplicationRequest::class, 'status', ReplicationRequest::STATUSES],
        [ReplicationRequest::class, 'payment_status', ReplicationRequest::PAYMENT_STATUSES],
        [PatientFollowUp::class, 'decision', array_keys(PatientFollowUp::DECISIONS)],
    ] as [$model, $column, $values]) {
        if ($model !== PatientFollowUp::class) {
            expect($model::count())->toBeGreaterThanOrEqual(50, $model);
        }
        foreach ($values as $value) {
            expect($model::where($column, $value)->exists())->toBeTrue("{$model} {$column}={$value}");
        }
    }

    // Every abnormal-reading filter on the check-ups page finds someone.
    foreach (array_keys(Patient::READINGS) as $reading) {
        expect(Patient::withReading($reading)->exists())->toBeTrue($reading);
    }

    // A resident has at most one open home-visit request.
    expect(Appointment::whereIn('status', Appointment::OPEN)->groupBy('resident_id')->havingRaw('COUNT(*) > 1')->pluck('resident_id'))->toBeEmpty();
});

it('shows the demo data on every admin page and filter', function () {
    $admin = User::factory()->create(['is_admin' => true, 'role' => 'Admin']);
    $this->artisan('demo:seed', ['--force' => true])->assertSuccessful();
    $this->actingAs($admin);

    foreach ([
        'admin', 'admin/emergencies', 'admin/live-map', 'admin/locations',
        'admin/operations/users', 'admin/operations/users?filter=pending', 'admin/operations/users?filter=GoBiker', 'admin/operations/users?filter=User',
        ...array_map(fn ($tab) => "admin/operations/sos?status={$tab}", ['open', 'sent', 'responding', 'resolved', 'all']),
        ...array_map(fn ($tab) => "admin/operations/appointments?status={$tab}", ['pending', 'accepted', 'completed', 'closed', 'all']),
        'admin/operations/patients', 'admin/operations/patients?reading=bp_high', 'admin/operations/patients?barangay=Poblacion',
        'admin/operations/gobiker-reports', 'admin/operations/gobiker-messages', 'admin/operations/gobiker-messages?status=unread',
        'admin/operations/donations', 'admin/operations/donations?status=paid&method=gcash', 'admin/operations/donations?frequency=monthly',
        'admin/operations/messages', 'admin/operations/messages?status=unread',
        'admin/operations/training-requests', 'admin/operations/training-requests?tier=advance&status=pending',
        'admin/operations/volunteers', 'admin/operations/volunteers?status=waitlisted',
        'admin/operations/chapter-inquiries', 'admin/operations/chapter-inquiries?status=scheduled&payment=paid',
    ] as $url) {
        $this->get('/'.$url)->assertOk();
    }
});

it('fills the live map with many GoBikers on real Bugallon streets, and keeps them live', function () {
    $admin = User::factory()->create(['is_admin' => true, 'role' => 'Admin']);
    $this->artisan('demo:seed', ['--force' => true, '--gobikers' => 200])->assertSuccessful();

    expect(User::where('role', 'GoBiker')->count())->toBe(200)
        ->and(User::pendingApproval()->count())->toBe(6)
        ->and(Location::count())->toBe(194)
        ->and(Location::distinct()->count('designated_barangay'))->toBe(count(\App\Support\Barangays::ALL));

    // Everyone stands on (or within a few metres of) a street point of their own barangay.
    $streets = json_decode(file_get_contents(database_path('data/bugallon-street-points.json')), true);
    expect(array_keys($streets))->toEqualCanonicalizing(\App\Support\Barangays::ALL);
    foreach (Location::all() as $location) {
        $nearest = collect($streets[$location->designated_barangay])
            ->map(fn ($p) => hypot(($p[0] - $location->latitude) * 111_320, ($p[1] - $location->longitude) * 107_000))
            ->min();
        expect($nearest)->toBeLessThan(25.0, "{$location->name} is {$nearest} m from a street");
    }

    // Some ride in groups: several GoBikers within 20 m of another one.
    $points = Location::get(['latitude', 'longitude'])->map(fn ($l) => [$l->latitude, $l->longitude])->all();
    $grouped = collect($points)->filter(fn ($p, $i) => collect($points)->except($i)
        ->contains(fn ($q) => hypot(($p[0] - $q[0]) * 111_320, ($p[1] - $q[1]) * 107_000) < 20))->count();
    expect($grouped)->toBeGreaterThan(20)->toBeLessThan(150);

    // Make the "on ronda" ones look stale, then let demo:live bring them back where they are.
    $onRonda = Location::whereNull('active_end_time')->count();
    expect($onRonda)->toBeGreaterThan(100);
    Location::query()->update(['last_seen_at' => now()->subHour()]);
    $before = Location::whereNull('active_end_time')->orderBy('id')->first();

    $this->artisan('demo:live', ['--once' => true])->assertSuccessful();

    expect(Location::where('last_seen_at', '>=', now()->subMinute())->count())->toBe($onRonda)
        ->and($before->fresh()->only('latitude', 'longitude'))->toBe($before->only('latitude', 'longitude'));

    $live = $this->actingAs($admin)->getJson('/admin/locations')->assertOk()->json();
    expect(collect($live)->where('status', 'active')->count())->toBe($onRonda);
});

it('fills both inboxes with hundreds of varied messages that still page fine', function () {
    $admin = User::factory()->create(['is_admin' => true, 'role' => 'Admin']);
    $this->artisan('demo:seed', ['--force' => true, '--messages' => 400])->assertSuccessful();

    expect(ContactMessage::count())->toBe(400)
        ->and(GobikerMessage::count())->toBe(400)
        ->and(ContactMessage::where('is_read', false)->count())->toBe(40)
        ->and(GobikerMessage::where('is_read', false)->count())->toBe(40)
        // Not the same few lines over and over.
        ->and(ContactMessage::distinct()->count('message'))->toBeGreaterThan(100)
        ->and(GobikerMessage::distinct()->count('message'))->toBeGreaterThan(150);

    $this->actingAs($admin);
    foreach (['admin/operations/messages', 'admin/operations/messages?status=unread&page=3', 'admin/operations/gobiker-messages', 'admin/operations/gobiker-messages?page=20'] as $url) {
        $this->get('/'.$url)->assertOk();
    }
});

it('links check-ups to the demo residents, not to real people', function () {
    $real = User::factory()->create(['role' => 'User', 'mobile' => '09171234567']);
    $this->artisan('demo:seed', ['--force' => true])->assertSuccessful();

    expect(Patient::whereNotNull('resident_id')->exists())->toBeTrue()
        ->and(Patient::where('resident_id', $real->id)->exists())->toBeFalse();
});

it('refuses to add the demo data twice unless asked to replace it', function () {
    $this->artisan('demo:seed', ['--force' => true])->assertSuccessful();
    $this->artisan('demo:seed', ['--force' => true])->assertFailed();

    $this->artisan('demo:seed', ['--force' => true, '--fresh' => true])->assertSuccessful();
    expect(User::where('role', 'User')->count())->toBe(45);
});

it('removes only the demo data', function () {
    $real = User::factory()->create(['role' => 'User', 'barangay' => 'Poblacion']);
    $realSos = SosRequest::create(['user_id' => $real->id, 'municipality' => 'Bugallon', 'barangay' => 'Poblacion', 'landmark' => 'Chapel']);
    $realMessage = ContactMessage::create(['name' => 'Real Person', 'email' => 'real@example.com', 'message' => 'Hello']);

    $this->artisan('demo:seed', ['--force' => true])->assertSuccessful();
    $this->artisan('demo:clear', ['--force' => true])->assertSuccessful();

    expect(User::pluck('id')->all())->toBe([$real->id])
        ->and(SosRequest::pluck('id')->all())->toBe([$realSos->id])
        ->and(ContactMessage::pluck('id')->all())->toBe([$realMessage->id]);
    foreach ([Patient::class, Appointment::class, Location::class, GobikerMessage::class, PatientFollowUp::class, Donation::class, TrainingRequest::class, VolunteerApplication::class, ReplicationRequest::class] as $model) {
        expect($model::count())->toBe(0, $model);
    }
});
