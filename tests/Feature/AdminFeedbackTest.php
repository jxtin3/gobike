<?php

use App\Models\Feedback;
use App\Models\SosRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeRating(array $overrides = []): Feedback
{
    $resident = User::factory()->create(['name' => 'Ana Reyes', 'role' => 'User', 'barangay' => 'Poblacion']);
    $gobiker = User::factory()->create(['name' => 'Maria Santos', 'role' => 'GoBiker', 'barangay' => 'Poblacion']);

    return Feedback::create(array_merge([
        'resident_id' => $resident->id,
        'gobiker_id' => $gobiker->id,
        'kind' => 'sos',
        'subject_id' => 1,
        'rating' => 5,
        'comment' => 'She arrived very quickly.',
    ], $overrides));
}

it('lists the ratings residents gave, newest first', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    makeRating(['comment' => 'The older one', 'created_at' => now()->subDay()]);
    makeRating(['subject_id' => 2, 'comment' => 'The newer one']);

    $this->actingAs($admin)
        ->get(route('admin.operations.feedback.index'))
        ->assertOk()
        ->assertSee('Maria Santos')
        ->assertSee('Ana Reyes')
        ->assertSee('The newer one')
        ->assertSeeInOrder(['The newer one', 'The older one'])
        ->assertSee('5/5')
        ->assertSee('Emergency SOS');
});

it('shows the average rating and how many were skipped', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    makeRating(['rating' => 5]);
    makeRating(['subject_id' => 2, 'rating' => 2]);
    // A skip stores no stars, and must not drag the average down.
    makeRating(['subject_id' => 3, 'rating' => null, 'comment' => null]);

    $this->actingAs($admin)
        ->get(route('admin.operations.feedback.index'))
        ->assertOk()
        ->assertSee('3.5')
        ->assertSee('Average of 2 ratings')
        ->assertSee('Skipped');
});

it('filters feedback by what it was for, and by skipped', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    makeRating(['kind' => 'sos', 'comment' => 'About the emergency']);
    makeRating(['kind' => 'checkup', 'subject_id' => 2, 'comment' => 'About the check-up']);
    makeRating(['kind' => 'sos', 'subject_id' => 3, 'rating' => null, 'comment' => null]);

    $this->actingAs($admin)->get(route('admin.operations.feedback.index', ['kind' => 'checkup']))
        ->assertOk()->assertSee('About the check-up')->assertDontSee('About the emergency');

    $this->actingAs($admin)->get(route('admin.operations.feedback.index', ['kind' => 'skipped']))
        ->assertOk()->assertDontSee('About the emergency')->assertDontSee('About the check-up');

    $this->actingAs($admin)->get(route('admin.operations.feedback.index', ['kind' => 'sos']))
        ->assertOk()->assertSee('About the emergency')->assertDontSee('About the check-up');
});

it('searches feedback by resident, GoBiker or comment', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    makeRating(['comment' => 'Very polite and careful']);

    $this->actingAs($admin)->get(route('admin.operations.feedback.index', ['search' => 'polite']))
        ->assertOk()->assertSee('Very polite and careful');

    $this->actingAs($admin)->get(route('admin.operations.feedback.index', ['search' => 'Maria']))
        ->assertOk()->assertSee('Very polite and careful');

    $this->actingAs($admin)->get(route('admin.operations.feedback.index', ['search' => 'nobody']))
        ->assertOk()->assertSee('No feedback matches');
});

it('links a rating to the emergency it was about, and copes when that is gone', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $rating = makeRating();
    $sos = SosRequest::create([
        'user_id' => $rating->resident_id, 'municipality' => 'Bugallon', 'barangay' => 'Poblacion',
        'address' => '12 Rizal St.', 'landmark' => 'Chapel', 'status' => 'resolved', 'resolved_at' => now(),
    ]);
    $rating->update(['subject_id' => $sos->id]);

    $this->actingAs($admin)->get(route('admin.operations.feedback.index'))
        ->assertOk()
        ->assertSee('data-subject-url="'.route('admin.operations.sos.show', $sos->id).'"', false);

    // The emergency was deleted later: the rating of the GoBiker still stands.
    $sos->delete();
    $this->actingAs($admin)->get(route('admin.operations.feedback.index'))
        ->assertOk()
        ->assertSee('Maria Santos')
        ->assertDontSee('data-subject-url', false);
});

it('says so when there is no feedback at all', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->get(route('admin.operations.feedback.index'))
        ->assertOk()
        ->assertSee('No feedback yet')
        ->assertDontSee('No feedback matches');
});
