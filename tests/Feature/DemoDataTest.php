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
        ->and(Location::where('status', 'emergency')->count())->toBe(1);

    foreach ([
        [Appointment::class, 'status', array_keys(Appointment::STATUSES)],
        [SosRequest::class, 'status', array_keys(SosRequest::STATUSES)],
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

it('fills the live map with many GoBikers in every Bugallon barangay, and keeps them moving', function () {
    $admin = User::factory()->create(['is_admin' => true, 'role' => 'Admin']);
    $this->artisan('demo:seed', ['--force' => true, '--gobikers' => 200])->assertSuccessful();

    expect(User::where('role', 'GoBiker')->count())->toBe(200)
        ->and(User::pendingApproval()->count())->toBe(6)
        ->and(Location::count())->toBe(194)
        ->and(Location::distinct()->count('designated_barangay'))->toBe(count(\App\Support\Barangays::ALL))
        ->and(Location::whereBetween('latitude', [15.89, 16.02])->whereBetween('longitude', [120.11, 120.26])->count())->toBe(194);

    // Make the "on ronda" ones look stale, then let demo:live bring them back.
    $onRonda = Location::whereNull('active_end_time')->count();
    expect($onRonda)->toBeGreaterThan(100);
    Location::query()->update(['last_seen_at' => now()->subHour()]);
    $before = Location::whereNull('active_end_time')->orderBy('id')->first();

    $this->artisan('demo:live', ['--once' => true])->assertSuccessful();

    $after = $before->fresh();
    expect(Location::where('last_seen_at', '>=', now()->subMinute())->count())->toBe($onRonda)
        ->and([$after->latitude, $after->longitude])->not->toBe([$before->latitude, $before->longitude]);

    $live = $this->actingAs($admin)->getJson('/admin/locations')->assertOk()->json();
    expect(collect($live)->where('status', 'active')->count())->toBeGreaterThan(100);
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
