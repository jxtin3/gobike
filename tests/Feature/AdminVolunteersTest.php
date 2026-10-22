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
        ->patch(route('admin.operations.volunteers.status', $application), ['application_status' => 'approved'])
        ->assertRedirect()
        ->assertSessionHas('success', "Carla Mendoza's application marked Approved.");

    expect($application->fresh()->application_status)->toBe('approved');
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
