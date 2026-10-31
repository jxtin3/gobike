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

it('keeps the retired free plan out of the filter but still labels old requests', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    makeTrainingRequest(['tier' => 'basic']);

    $this->actingAs($admin)->get(route('admin.operations.training-requests.index'))
        ->assertSee('<option value="advance"', false)
        ->assertDontSee('<option value="basic"', false)
        ->assertSee('Basic (no longer offered)'); // the old request's own label in the table
});
