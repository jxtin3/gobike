<?php

use App\Models\User;
use App\Models\VolunteerApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeVolunteerApplication(array $overrides = []): VolunteerApplication
{
    return VolunteerApplication::create(array_merge([
        'first_name' => 'Carla',
        'last_name' => 'Mendoza',
        'email' => 'carla@example.com',
        'phone' => '09191234567',
        'age' => 16,
        'occupation_status' => 'student',
        'school' => 'Bugallon National High School',
        'barangay' => 'Poblacion',
        'municipality' => 'Bugallon',
        'province' => 'Pangasinan',
        'availability' => 'weekends',
        'has_bike' => true,
        'motivation' => 'I want to help my community.',
        'emergency_name' => 'Rosa Mendoza',
        'emergency_phone' => '09201234567',
        'guardian_name' => 'Rosa Mendoza',
        'guardian_phone' => '09201234567',
        'guardian_consent' => true,
        'application_status' => 'pending',
    ], $overrides));
}

it('lists volunteer applications and searches by town', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    makeVolunteerApplication();
    makeVolunteerApplication(['first_name' => 'Dino', 'municipality' => 'Lingayen']);

    $this->actingAs($admin)
        ->get(route('admin.operations.volunteers.index', ['search' => 'Lingayen']))
        ->assertOk()
        ->assertSee('Dino Mendoza')
        ->assertDontSee('Carla Mendoza');
});

it('shows a volunteer application with its guardian details', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $application = makeVolunteerApplication();

    $this->actingAs($admin)
        ->get(route('admin.operations.volunteers.show', $application))
        ->assertOk()
        ->assertSee('Carla Mendoza')
        ->assertSee('Guardian / Parent')
        ->assertSee('for="application-status"', false)
        ->assertSee('data-confirm-title="Reject this application?"', false);
});

it('updates a volunteer application status', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $application = makeVolunteerApplication();

    $this->actingAs($admin)
        ->patch(route('admin.operations.volunteers.status', $application), ['application_status' => 'waiting'])
        ->assertRedirect()
        ->assertSessionHas('success', "Carla Mendoza's application marked Waiting for class.");

    expect($application->fresh()->application_status)->toBe('waiting');
});

it('rejects an unknown volunteer status', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $application = makeVolunteerApplication();

    $this->actingAs($admin)
        ->patch(route('admin.operations.volunteers.status', $application), ['application_status' => 'hired'])
        ->assertSessionHasErrors('application_status');
});

it('deletes a volunteer application', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $application = makeVolunteerApplication();

    $this->actingAs($admin)
        ->delete(route('admin.operations.volunteers.destroy', $application))
        ->assertRedirect(route('admin.operations.volunteers.index'))
        ->assertSessionHas('success');

    $this->assertModelMissing($application);
});

// ---------------------------------------------------------------- classes

it('schedules a class with the volunteers who waited longest, up to a full class', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    foreach (range(1, 27) as $i) {
        makeVolunteerApplication([
            'first_name' => "Waiting{$i}", 'email' => "w{$i}@example.com", 'application_status' => 'waiting',
            'created_at' => now()->subDays(40 - $i),
        ]);
    }
    $new = makeVolunteerApplication(['first_name' => 'Brand', 'email' => 'new@example.com']);

    $this->actingAs($admin)->get(route('admin.operations.volunteers.index'))
        ->assertOk()
        ->assertSee('27 volunteers are waiting: enough for a class')
        ->assertSee('Schedule class for 25');

    $this->post(route('admin.operations.volunteers.classes.store'), [
        'starts_on' => now()->addWeek()->toDateString(), 'venue' => 'Bugallon Municipal Hall',
    ])->assertRedirect()->assertSessionHas('success');

    $class = \App\Models\VolunteerClass::sole();
    expect($class->applications()->count())->toBe(25)
        ->and($class->applications()->pluck('first_name')->sort()->values()->all())
            ->not->toContain('Waiting26', 'Waiting27', 'Brand')
        ->and(VolunteerApplication::where('application_status', 'waiting')->count())->toBe(2)
        ->and($new->fresh()->application_status)->toBe('pending');

    // The class filter shows just that class.
    $this->get(route('admin.operations.volunteers.index', ['class' => $class->id]))
        ->assertSee('Waiting1 Mendoza')
        ->assertDontSee('Waiting27 Mendoza');

    // After the class, everyone in it is trained.
    $this->patch(route('admin.operations.volunteers.classes.complete', $class))->assertSessionHas('success');
    expect(VolunteerApplication::where('application_status', 'trained')->count())->toBe(25)
        ->and($class->fresh()->completed_at)->not->toBeNull();
});

it('needs someone waiting, a future date and a venue to schedule a class', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $this->post(route('admin.operations.volunteers.classes.store'), ['starts_on' => now()->subDay()->toDateString()])
        ->assertSessionHasErrors(['starts_on', 'venue']);

    $this->post(route('admin.operations.volunteers.classes.store'), [
        'starts_on' => now()->addWeek()->toDateString(), 'venue' => 'Hall',
    ])->assertSessionHas('error');
    expect(\App\Models\VolunteerClass::count())->toBe(0);
});

it('takes a volunteer out of their class when sent back to waiting', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $class = \App\Models\VolunteerClass::create(['starts_on' => now()->addWeek(), 'venue' => 'Hall']);
    $application = makeVolunteerApplication(['application_status' => 'scheduled', 'volunteer_class_id' => $class->id]);

    $this->actingAs($admin)->get(route('admin.operations.volunteers.show', $application))
        ->assertSee('Hall')
        ->assertSee('href="tel:09191234567"', false);

    $this->patch(route('admin.operations.volunteers.status', $application), ['application_status' => 'waiting']);
    expect($application->fresh())->application_status->toBe('waiting')->volunteer_class_id->toBeNull();
});
