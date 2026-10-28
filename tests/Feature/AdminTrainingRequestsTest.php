<?php

use App\Models\TrainingRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeTrainingRequest(array $overrides = []): TrainingRequest
{
    return TrainingRequest::create(array_merge([
        'name' => 'Lito Garcia',
        'email' => 'lito@example.com',
        'phone' => '09181234567',
        'address' => 'Purok 3, Barangay Poblacion',
        'tier' => 'standard',
        'message' => 'Weekends work best.',
        'status' => 'pending',
    ], $overrides));
}

it('lists training requests with tier and status labels', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    makeTrainingRequest();

    $this->actingAs($admin)
        ->get(route('admin.operations.training-requests.index'))
        ->assertOk()
        ->assertSee('Lito Garcia')
        ->assertSee('Standard (₱1,500)')
        ->assertSee('Pending')
        ->assertSee('id="trainingDialog"', false);
});

it('filters training requests by tier and status', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    makeTrainingRequest(['name' => 'Basic Pending', 'tier' => 'basic']);
    makeTrainingRequest(['name' => 'Advance Done', 'tier' => 'advance', 'status' => 'completed']);

    $this->actingAs($admin)
        ->get(route('admin.operations.training-requests.index', ['tier' => 'advance', 'status' => 'completed']))
        ->assertOk()
        ->assertSee('Advance Done')
        ->assertDontSee('Basic Pending');

    $this->actingAs($admin)
        ->get(route('admin.operations.training-requests.index', ['tier' => 'standard']))
        ->assertOk()
        ->assertSee('No requests match');
});

it('filters by training date, with upcoming ones soonest first', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    makeTrainingRequest(['name' => 'Next Month', 'preferred_date' => now()->addDays(30)->toDateString(), 'preferred_window' => 'morning']);
    makeTrainingRequest(['name' => 'Tomorrow Person', 'preferred_date' => now()->addDay()->toDateString(), 'preferred_window' => 'afternoon']);
    makeTrainingRequest(['name' => 'Last Week', 'preferred_date' => now()->subWeek()->toDateString(), 'preferred_window' => 'morning']);

    $this->actingAs($admin)
        ->get(route('admin.operations.training-requests.index', ['when' => 'upcoming']))
        ->assertOk()
        ->assertSeeInOrder(['Tomorrow Person', 'Next Month'])
        ->assertDontSee('Last Week');

    $this->get(route('admin.operations.training-requests.index', ['when' => 'past']))
        ->assertSee('Last Week')
        ->assertDontSee('Tomorrow Person');
});

it('only lists the retired free plan in the filter while old requests use it', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    makeTrainingRequest();

    $this->actingAs($admin)->get(route('admin.operations.training-requests.index'))
        ->assertSee('Advance (₱2,500)')
        ->assertDontSee('Basic (no longer offered)');

    makeTrainingRequest(['tier' => 'basic']);
    $this->get(route('admin.operations.training-requests.index'))->assertSee('Basic (no longer offered)');
});

it('updates the status of a training request', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $request = makeTrainingRequest();

    $this->actingAs($admin)
        ->patch(route('admin.operations.training-requests.status', $request), ['status' => 'contacted'])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($request->fresh()->status)->toBe('contacted');
});

it('rejects an unknown training request status', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $request = makeTrainingRequest();

    $this->actingAs($admin)
        ->patch(route('admin.operations.training-requests.status', $request), ['status' => 'archived'])
        ->assertSessionHasErrors('status');

    expect($request->fresh()->status)->toBe('pending');
});

it('deletes a training request', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $request = makeTrainingRequest();

    $this->actingAs($admin)
        ->delete(route('admin.operations.training-requests.destroy', $request))
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertModelMissing($request);
});

it('shows pending training requests in the sidebar', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    makeTrainingRequest();
    makeTrainingRequest();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertSee('data-nav-badge="training"><span class="count-num">2</span>', false);
});
