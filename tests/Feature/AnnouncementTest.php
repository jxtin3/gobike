<?php

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true, 'name' => 'Admin Ana']);
});

function announce(array $overrides = []): Announcement
{
    // unguarded so a test can also set created_at
    return Announcement::unguarded(fn () => Announcement::create(array_merge(['title' => 'Schedule update', 'body' => 'Ronda starts at 7:30 AM.', 'is_active' => true], $overrides)));
}

it('lets the admin post an announcement', function () {
    $this->actingAs($this->admin, 'web')
        ->post(route('admin.operations.announcements.store'), [
            'title' => 'Ronda schedule update',
            'body' => "Tomorrow's ronda will start at 7:30 AM.",
            'is_active' => '1',
        ])
        ->assertRedirect(route('admin.operations.announcements.index'))
        ->assertSessionHas('success');

    $announcement = Announcement::sole();
    expect($announcement->is_active)->toBeTrue()
        ->and($announcement->created_by)->toBe($this->admin->id)
        ->and($announcement->status_label)->toBe('Live');

    $this->actingAs($this->admin, 'web')
        ->get(route('admin.operations.announcements.index'))
        ->assertOk()
        ->assertSee('Ronda schedule update')
        ->assertSee('Admin Ana')
        ->assertSee('1</b> live now', false);
});

it('saves an announcement as hidden when the box is not ticked', function () {
    $this->actingAs($this->admin, 'web')
        ->post(route('admin.operations.announcements.store'), ['title' => 'Draft', 'body' => 'Not ready.'])
        ->assertRedirect();

    expect(Announcement::sole()->status_label)->toBe('Hidden');
});

it('validates the announcement', function () {
    $this->actingAs($this->admin, 'web')
        ->post(route('admin.operations.announcements.store'), [
            'title' => '', 'body' => str_repeat('a', 1001), 'expires_at' => now()->subDay()->format('Y-m-d\TH:i'),
        ])
        ->assertSessionHasErrors(['title', 'body', 'expires_at']);

    expect(Announcement::count())->toBe(0);
});

it('edits and deletes an announcement', function () {
    $announcement = announce();

    $this->actingAs($this->admin, 'web')
        ->put(route('admin.operations.announcements.update', $announcement), ['title' => 'New title', 'body' => 'New body.'])
        ->assertRedirect(route('admin.operations.announcements.index'));

    $announcement->refresh();
    expect($announcement->title)->toBe('New title')
        ->and($announcement->is_active)->toBeFalse(); // box not ticked: hidden

    $this->actingAs($this->admin, 'web')
        ->delete(route('admin.operations.announcements.destroy', $announcement))
        ->assertRedirect();

    $this->assertModelMissing($announcement);
});

it('lets an already expired announcement be edited without changing its end date', function () {
    $announcement = announce(['expires_at' => now()->subDay()]);

    $this->actingAs($this->admin, 'web')
        ->put(route('admin.operations.announcements.update', $announcement), [
            'title' => 'Still saved', 'body' => 'Body.', 'is_active' => '1',
            'expires_at' => $announcement->expires_at->format('Y-m-d\TH:i'),
        ])
        ->assertSessionHasNoErrors();

    expect($announcement->fresh()->title)->toBe('Still saved')
        ->and($announcement->fresh()->status_label)->toBe('Expired');
});

it('only shows GoBikers live announcements, newest first', function () {
    announce(['title' => 'Old but live', 'created_at' => now()->subDays(3)]);
    announce(['title' => 'Newest', 'created_at' => now()->subHour()]);
    announce(['title' => 'Hidden one', 'is_active' => false]);
    announce(['title' => 'Expired one', 'expires_at' => now()->subMinute()]);
    announce(['title' => 'Ends later', 'expires_at' => now()->addWeek(), 'created_at' => now()->subDay()]);

    Sanctum::actingAs(User::factory()->create(['role' => 'GoBiker', 'status' => 'Active']));

    $response = $this->getJson(route('api.gobiker.announcements'))->assertOk();

    expect(collect($response->json('announcements'))->pluck('title')->all())->toBe(['Newest', 'Ends later', 'Old but live'])
        ->and($response->json('announcements.0'))->toHaveKeys(['id', 'title', 'body', 'posted_at', 'expires_at']);
});

it('is only for signed-in GoBikers', function () {
    $this->getJson(route('api.gobiker.announcements'))->assertUnauthorized();

    Sanctum::actingAs(User::factory()->create(['role' => 'User']));
    $this->getJson(route('api.gobiker.announcements'))->assertForbidden();
});

it('shows announcement text as plain text, never as HTML', function () {
    announce(['title' => '<script>alert(1)</script>', 'body' => '<b>bold</b>']);

    $this->actingAs($this->admin, 'web')
        ->get(route('admin.operations.announcements.index'))
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;script&gt;', false);
});
