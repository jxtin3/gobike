<?php

use App\Models\Feedback;
use App\Models\SosRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeGobiker(string $name = 'Maria Santos', string $barangay = 'Poblacion'): User
{
    return User::factory()->create(['name' => $name, 'role' => 'GoBiker', 'barangay' => $barangay]);
}

/** A rating. Pass `gobiker_id` / `resident_id` to rate the same people more than once. */
function makeRating(array $overrides = []): Feedback
{
    $resident = isset($overrides['resident_id'])
        ? null
        : User::factory()->create(['name' => 'Ana Reyes', 'role' => 'User', 'barangay' => 'Poblacion']);

    return Feedback::create(array_merge([
        'resident_id' => $resident?->id,
        'gobiker_id' => array_key_exists('gobiker_id', $overrides) ? null : makeGobiker()->id,
        'kind' => 'sos',
        'subject_id' => 1,
        'rating' => 5,
        'comment' => 'She arrived very quickly.',
    ], $overrides));
}

// ---------------------------------------------------------------------- the list: one row per GoBiker

it('lists each GoBiker once, with their average and how many ratings that is', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $maria = makeGobiker('Maria Santos');
    makeRating(['gobiker_id' => $maria->id, 'subject_id' => 1, 'rating' => 5]);
    makeRating(['gobiker_id' => $maria->id, 'subject_id' => 2, 'rating' => 3]);
    // A skip has no stars: counted as skipped, never dragging the average down.
    makeRating(['gobiker_id' => $maria->id, 'subject_id' => 3, 'rating' => null, 'comment' => null]);

    $response = $this->actingAs($admin)
        ->get(route('admin.operations.feedback.index'))
        ->assertOk()
        ->assertSee('Maria Santos')
        ->assertSee('4.0/5')
        ->assertSee('2 ratings')
        ->assertSee('1 skipped')
        // No comments on this page: they are on the GoBiker's own.
        ->assertDontSee('She arrived very quickly.');

    // One row for her, not one per rating.
    expect(substr_count($response->getContent(), 'data-href="'))->toBe(1);
});

it('puts the best-rated GoBiker first and one who only has skips last', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    makeRating(['gobiker_id' => makeGobiker('Cara Skipped')->id, 'rating' => null, 'comment' => null]);
    makeRating(['gobiker_id' => makeGobiker('Dina Fair')->id, 'subject_id' => 2, 'rating' => 3]);
    makeRating(['gobiker_id' => makeGobiker('Ben Best')->id, 'subject_id' => 3, 'rating' => 5]);
    makeRating(['gobiker_id' => makeGobiker('Eli Good')->id, 'subject_id' => 4, 'rating' => 4]);

    $this->actingAs($admin)
        ->get(route('admin.operations.feedback.index'))
        ->assertOk()
        ->assertSeeInOrder(['Ben Best', 'Eli Good', 'Dina Fair', 'Cara Skipped'])
        ->assertSee('Skipped only');
});

it('keeps the order across pages', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    // 16 GoBikers: a page holds 15, so the worst one has to fall onto the second page.
    foreach (range(1, 16) as $i) {
        makeRating(['gobiker_id' => makeGobiker("Rider number {$i}")->id, 'subject_id' => $i, 'rating' => $i === 16 ? 1 : 5]);
    }

    $this->actingAs($admin)
        ->get(route('admin.operations.feedback.index'))
        ->assertOk()
        ->assertDontSee('Rider number 16')
        ->assertSee('Rider number 15');

    $this->get(route('admin.operations.feedback.index', ['page' => 2]))
        ->assertOk()
        ->assertSee('Rider number 16');
});

it('shows the overall average, how many GoBikers were rated and how many ratings were skipped', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    makeRating(['subject_id' => 1, 'rating' => 5]);
    makeRating(['subject_id' => 2, 'rating' => 2]);
    makeRating(['subject_id' => 3, 'rating' => null, 'comment' => null]);

    $this->actingAs($admin)
        ->get(route('admin.operations.feedback.index'))
        ->assertOk()
        ->assertSee('3.5')
        ->assertSee('Average of 2 ratings')
        ->assertSee('GoBikers rated')
        ->assertSee('Skipped by the resident');
});

it('narrows the GoBikers by what the rating was for, and works out their figures again', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $maria = makeGobiker('Maria Santos');
    makeRating(['gobiker_id' => $maria->id, 'kind' => 'sos', 'subject_id' => 1, 'rating' => 5]);
    makeRating(['gobiker_id' => $maria->id, 'kind' => 'checkup', 'subject_id' => 2, 'rating' => 1]);
    makeRating(['gobiker_id' => makeGobiker('Jose Lim')->id, 'kind' => 'sos', 'subject_id' => 3, 'rating' => 4]);

    $this->actingAs($admin)->get(route('admin.operations.feedback.index', ['kind' => 'checkup']))
        ->assertOk()->assertSee('Maria Santos')->assertSee('1.0/5')->assertDontSee('Jose Lim');

    $this->actingAs($admin)->get(route('admin.operations.feedback.index', ['kind' => 'sos']))
        ->assertOk()->assertSee('Maria Santos')->assertSee('5.0/5')->assertSee('Jose Lim');
});

it('searches the GoBikers by name or barangay', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    makeRating(['gobiker_id' => makeGobiker('Maria Santos', 'Poblacion')->id]);
    makeRating(['gobiker_id' => makeGobiker('Jose Lim', 'Hacienda')->id, 'subject_id' => 2]);

    $this->actingAs($admin)->get(route('admin.operations.feedback.index', ['search' => 'Maria']))
        ->assertOk()->assertSee('Maria Santos')->assertDontSee('Jose Lim');

    $this->actingAs($admin)->get(route('admin.operations.feedback.index', ['search' => 'Hacienda']))
        ->assertOk()->assertSee('Jose Lim')->assertDontSee('Maria Santos');

    $this->actingAs($admin)->get(route('admin.operations.feedback.index', ['search' => 'nobody']))
        ->assertOk()->assertSee('No GoBiker matches');
});

it('opens a page for each GoBiker, not a pop-up', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $maria = makeGobiker('Maria Santos');
    makeRating(['gobiker_id' => $maria->id]);

    $this->actingAs($admin)->get(route('admin.operations.feedback.index'))
        ->assertOk()
        ->assertSee('data-href="'.route('admin.operations.feedback.show', $maria).'"', false)
        ->assertDontSee('feedbackDialog')
        ->assertDontSee('data-dialog', false);
});

it('still lists the ratings of a GoBiker whose account was deleted, without a page to open', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    makeRating(['gobiker_id' => null, 'comment' => 'From before they left']);

    $this->actingAs($admin)->get(route('admin.operations.feedback.index'))
        ->assertOk()
        ->assertSee('Deleted GoBiker')
        ->assertDontSee('data-href', false);
});

it('says so when there is no feedback at all', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->get(route('admin.operations.feedback.index'))
        ->assertOk()
        ->assertSee('No feedback yet')
        ->assertDontSee('No GoBiker matches');
});

// ---------------------------------------------------------------------- a GoBiker's page

it("shows every resident's feedback about the GoBiker, and nobody else's", function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $maria = makeGobiker('Maria Santos');
    $jose = makeGobiker('Jose Lim');
    makeRating(['gobiker_id' => $maria->id, 'subject_id' => 1, 'rating' => 2, 'comment' => 'Two stars', 'created_at' => now()->subDay()]);
    makeRating(['gobiker_id' => $maria->id, 'subject_id' => 2, 'rating' => 5, 'comment' => 'Five stars', 'created_at' => now()->subDays(3)]);
    // A skip has no stars; it belongs at the bottom, not among the one-star ratings.
    makeRating(['gobiker_id' => $maria->id, 'subject_id' => 3, 'rating' => null, 'comment' => 'Skipped this one']);
    makeRating(['gobiker_id' => $maria->id, 'subject_id' => 4, 'rating' => 4, 'comment' => 'Four stars', 'created_at' => now()->subDays(2)]);
    makeRating(['gobiker_id' => $jose->id, 'subject_id' => 5, 'rating' => 5, 'comment' => 'About somebody else']);

    $this->actingAs($admin)
        ->get(route('admin.operations.feedback.show', $maria))
        ->assertOk()
        ->assertSee('Maria Santos')
        ->assertSee('Ana Reyes')
        ->assertSeeInOrder(['Five stars', 'Four stars', 'Two stars', 'Skipped this one'])
        ->assertSee('5/5')
        ->assertSee('Emergency SOS')
        ->assertDontSee('About somebody else')
        // Her figures, from her ratings only: (5 + 4 + 2) / 3.
        ->assertSee('3.7')
        ->assertSee('Average of 3 ratings')
        ->assertSee('Back to feedback');
});

it('shows a long comment whole, not cut short', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $maria = makeGobiker();
    $long = 'She came within minutes and stayed until the ambulance arrived. '.str_repeat('Very kind and careful. ', 8).'The end.';
    makeRating(['gobiker_id' => $maria->id, 'comment' => $long]);

    $this->actingAs($admin)->get(route('admin.operations.feedback.show', $maria))
        ->assertOk()
        ->assertSee($long);
});

it('narrows a GoBiker\'s feedback by what it was for, by skipped, and by a search', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $maria = makeGobiker();
    makeRating(['gobiker_id' => $maria->id, 'kind' => 'sos', 'comment' => 'About the emergency']);
    makeRating(['gobiker_id' => $maria->id, 'kind' => 'checkup', 'subject_id' => 2, 'comment' => 'About the check-up']);
    makeRating(['gobiker_id' => $maria->id, 'kind' => 'sos', 'subject_id' => 3, 'rating' => null, 'comment' => null]);

    $this->actingAs($admin)->get(route('admin.operations.feedback.show', [$maria, 'kind' => 'checkup']))
        ->assertOk()->assertSee('About the check-up')->assertDontSee('About the emergency');

    $this->actingAs($admin)->get(route('admin.operations.feedback.show', [$maria, 'kind' => 'skipped']))
        ->assertOk()->assertDontSee('About the emergency')->assertDontSee('About the check-up')->assertSee('Skipped');

    $this->actingAs($admin)->get(route('admin.operations.feedback.show', [$maria, 'kind' => 'sos']))
        ->assertOk()->assertSee('About the emergency')->assertDontSee('About the check-up');

    $this->actingAs($admin)->get(route('admin.operations.feedback.show', [$maria, 'search' => 'emergency']))
        ->assertOk()->assertSee('About the emergency')->assertDontSee('About the check-up');

    $this->actingAs($admin)->get(route('admin.operations.feedback.show', [$maria, 'search' => 'Ana']))
        ->assertOk()->assertSee('About the emergency');

    $this->actingAs($admin)->get(route('admin.operations.feedback.show', [$maria, 'search' => 'nobody']))
        ->assertOk()->assertSee('No feedback matches');
});

it("keeps the best-first order across a GoBiker's pages", function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $maria = makeGobiker();
    // 16 ratings: a page holds 15, so the worst of them has to fall onto the second page.
    foreach (range(1, 16) as $i) {
        makeRating(['gobiker_id' => $maria->id, 'subject_id' => $i, 'rating' => $i === 16 ? 1 : 5, 'comment' => "Rating number {$i}"]);
    }

    $this->actingAs($admin)->get(route('admin.operations.feedback.show', $maria))
        ->assertOk()->assertDontSee('Rating number 16')->assertSee('Rating number 15');

    $this->get(route('admin.operations.feedback.show', [$maria, 'page' => 2]))
        ->assertOk()->assertSee('Rating number 16');
});

it('links a rating to the emergency it was about, and copes when that is gone', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $rating = makeRating();
    $sos = SosRequest::create([
        'user_id' => $rating->resident_id, 'municipality' => 'Bugallon', 'barangay' => 'Poblacion',
        'address' => '12 Rizal St.', 'landmark' => 'Chapel', 'status' => 'resolved', 'resolved_at' => now(),
    ]);
    $rating->update(['subject_id' => $sos->id]);
    $page = route('admin.operations.feedback.show', $rating->gobiker_id);

    $this->actingAs($admin)->get($page)
        ->assertOk()
        ->assertSee('href="'.route('admin.operations.sos.show', $sos->id).'"', false);

    // The emergency was deleted later: the rating of the GoBiker still stands.
    $sos->delete();
    $this->actingAs($admin)->get($page)
        ->assertOk()
        ->assertSee('She arrived very quickly.')
        ->assertDontSee(route('admin.operations.sos.show', $sos->id), false);
});

it('says so when the GoBiker has no feedback yet', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $maria = makeGobiker('Maria Santos');

    $this->actingAs($admin)->get(route('admin.operations.feedback.show', $maria))
        ->assertOk()
        ->assertSee('No feedback for this GoBiker yet');
});

it('still opens the page of someone who is no longer a GoBiker but has feedback', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $rating = makeRating();
    User::whereKey($rating->gobiker_id)->update(['role' => 'User']);

    $this->actingAs($admin)->get(route('admin.operations.feedback.show', $rating->gobiker_id))
        ->assertOk()
        ->assertSee('She arrived very quickly.');
});

it('has no feedback page for an account that is not a GoBiker and was never rated', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $resident = User::factory()->create(['role' => 'User']);

    $this->actingAs($admin)->get(route('admin.operations.feedback.show', $resident))->assertNotFound();
});

it("keeps a GoBiker's feedback page for admins only", function () {
    $maria = makeGobiker();
    makeRating(['gobiker_id' => $maria->id]);

    $this->get(route('admin.operations.feedback.show', $maria))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create(['is_admin' => false, 'role' => 'GoBiker']))
        ->get(route('admin.operations.feedback.show', $maria))
        ->assertForbidden();
});
