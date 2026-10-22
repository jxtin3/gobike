<?php

use App\Models\ImpactStat;
use App\Models\Location;
use App\Models\News;
use App\Models\User;
use App\Support\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('labels future-dated published news as scheduled', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    News::create([
        'title' => 'Coming soon', 'slug' => 'coming-soon', 'body' => 'Later.',
        'is_published' => true, 'published_at' => now()->addWeek(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.operations.news.index'))
        ->assertOk()
        ->assertSee('Scheduled')
        ->assertDontSee('>Published<', false);
});

it('replaces a news image and removes the old file only after saving', function () {
    Storage::fake(Media::disk());
    $admin = User::factory()->create(['is_admin' => true]);
    Storage::disk(Media::disk())->put('news/old.jpg', 'old');
    $news = News::create([
        'title' => 'Ride day', 'slug' => 'ride-day', 'body' => 'Story', 'image_path' => 'news/old.jpg',
        'is_published' => false,
    ]);

    $this->actingAs($admin)
        ->put(route('admin.operations.news.update', $news), [
            'title' => 'Ride day', 'body' => 'Story', 'status' => 'Draft',
            'image' => UploadedFile::fake()->image('new.jpg'),
        ])
        ->assertRedirect(route('admin.operations.news.index'));

    $news->refresh();
    expect($news->image_path)->not->toBe('news/old.jpg');
    Storage::disk(Media::disk())->assertExists($news->image_path);
    Storage::disk(Media::disk())->assertMissing('news/old.jpg');
});

it('deletes news from its own page and returns to the list', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $news = News::create(['title' => 'Old post', 'slug' => 'old-post', 'body' => 'Story', 'is_published' => false]);

    $this->actingAs($admin)
        ->from(route('admin.operations.news.show', $news))
        ->delete(route('admin.operations.news.destroy', $news))
        ->assertRedirect(route('admin.operations.news.index'));

    $this->assertModelMissing($news);
});

it('adds and removes homepage statistics', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->post(route('admin.operations.homepage-data.impact-stats.store'), [
            'section' => 'highlight', 'label' => 'Barangays reached', 'value' => '25',
        ])
        ->assertRedirect(route('admin.operations.homepage-data.index'));

    $stat = ImpactStat::where('label', 'Barangays reached')->sole();
    expect($stat->section)->toBe('highlight');

    $this->actingAs($admin)
        ->delete(route('admin.operations.homepage-data.impact-stats.destroy', $stat))
        ->assertRedirect(route('admin.operations.homepage-data.index'));

    $this->assertModelMissing($stat);
});

it('keeps homepage statistic errors in a bag for the form that failed', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->post(route('admin.operations.homepage-data.impact-stats.store'), [
            'section' => 'badge', 'form_key' => 'create_badge', 'label' => 'Typed label', 'value' => '',
        ])
        ->assertSessionHasErrorsIn('create_badge', ['value']);
});

it('shows a homepage statistic error next to the form it came from', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->from(route('admin.operations.homepage-data.index'))
        ->post(route('admin.operations.homepage-data.impact-stats.store'), [
            'section' => 'badge', 'form_key' => 'create_badge', 'label' => 'Typed label', 'value' => '',
        ]);

    $html = $this->actingAs($admin)->get(route('admin.operations.homepage-data.index'))->assertOk()->getContent();

    // Only the badge form gets the typed text back and the error message.
    expect(substr_count($html, 'value="Typed label"'))->toBe(1)
        ->and(substr_count($html, 'id="value-error"'))->toBe(1);
});

it('only puts current GoBikers on the live map', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $gobiker = User::factory()->create(['role' => 'GoBiker', 'name' => 'On Ronda']);
    $former = User::factory()->create(['role' => 'User', 'name' => 'Former GoBiker']);
    Location::create(['user_id' => $gobiker->id, 'latitude' => 16.0, 'longitude' => 120.3, 'status' => 'active', 'last_seen_at' => now()]);
    Location::create(['user_id' => $former->id, 'latitude' => 16.1, 'longitude' => 120.4, 'status' => 'active', 'last_seen_at' => now()]);

    $this->actingAs($admin)
        ->getJson(route('admin.locations.index'))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.name', 'On Ronda')
        ->assertJsonPath('0.status', 'active');
});
