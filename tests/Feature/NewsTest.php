<?php

use App\Models\News;
use App\Models\User;

function makeNews(array $overrides = []): News
{
    return News::create(array_merge([
        'title' => 'Sample News',
        'slug' => 'sample-news',
        'body' => "First paragraph.\nSecond line.",
        'is_published' => true,
        'published_at' => now()->subDay(),
    ], $overrides));
}

// news
it('news page', function () {
    $this->get('/news')->assertStatus(200);
});

// link previews (Facebook, WhatsApp, X)
it('shares the Go Bike photo as a large banner when a page is shared', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('<meta property="og:image"       content="'.asset('images/og-share.jpg').'"', false)
        ->assertSee('property="og:image:width"  content="1200"', false)
        ->assertSee('property="og:image:height" content="630"', false)
        ->assertSee('name="twitter:card"        content="summary_large_image"', false)
        ->assertDontSee('property="og:image"       content="'.asset('images/gobike-logo.png').'"', false);

    expect(public_path('images/og-share.jpg'))->toBeFile();
    expect(getimagesize(public_path('images/og-share.jpg'))[0])->toBe(1200);
});

it('shares a news photo when the article has one and the Go Bike photo when it has none', function () {
    makeNews(['slug' => 'no-photo', 'image_path' => null]);
    makeNews(['title' => 'With Photo', 'slug' => 'with-photo', 'image_path' => 'https://example.test/own-photo.jpg']);

    $this->get('/news/no-photo')
        ->assertOk()
        ->assertSee('property="og:image"       content="'.asset('images/og-share.jpg').'"', false);

    $this->get('/news/with-photo')
        ->assertOk()
        ->assertSee('property="og:image"       content="https://example.test/own-photo.jpg"', false)
        ->assertDontSee('og-share.jpg', false);
});

it('allows admins to edit the publication date of news', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $news = makeNews([
        'title' => 'Editable News',
        'slug' => 'editable-news',
        'body' => 'News body',
        'published_at' => '2026-10-01',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.operations.news.edit', $news))
        ->assertOk()
        ->assertSee('name="published_at"', false)
        ->assertSee('value="2026-10-01"', false);

    $this->actingAs($admin)
        ->put(route('admin.operations.news.update', $news), [
            'title' => $news->title,
            'body' => $news->body,
            'status' => 'Published',
            'published_at' => '2026-09-20',
        ])
        ->assertRedirect(route('admin.operations.news.index'));

    expect($news->fresh()->published_at->toDateString())->toBe('2026-09-20');
});

it('lets admins save a short summary when creating and updating news', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->post(route('admin.operations.news.store'), [
            'title' => 'With Summary',
            'excerpt' => 'A short teaser.',
            'body' => 'Full body text.',
            'status' => 'Published',
            'published_at' => '2026-09-01',
        ])
        ->assertRedirect(route('admin.operations.news.index'));

    $news = News::where('title', 'With Summary')->firstOrFail();
    expect($news->excerpt)->toBe('A short teaser.')
        ->and($news->summary)->toBe('A short teaser.');

    $this->actingAs($admin)
        ->put(route('admin.operations.news.update', $news), [
            'title' => 'With Summary',
            'excerpt' => 'Updated teaser.',
            'body' => 'Full body text.',
            'status' => 'Draft',
        ])
        ->assertRedirect(route('admin.operations.news.index'));

    expect($news->fresh()->excerpt)->toBe('Updated teaser.')
        ->and($news->fresh()->is_published)->toBeFalse()
        ->and($news->fresh()->published_at)->toBeNull();
});

it('displays the permanent news stories on the homepage', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Padyarescue Incorporated won the 2024 Healthy Aging Prize for Asian Innovation')
        ->assertSee('GMA News Features Go Bike Project: Bringing Healthcare and Bayanihan Closer to Communities')
        ->assertDontSee('Daily Tribune Recognizes');
});

// published news
it('shows published news but hides draft news', function () {
    makeNews(['title' => 'Published Test News', 'slug' => 'published-test-news']);
    makeNews(['title' => 'Draft Test News', 'slug' => 'draft-test-news', 'is_published' => false, 'published_at' => null]);

    $this->get('/news')
        ->assertStatus(200)
        ->assertSee('Published Test News')
        ->assertDontSee('Draft Test News');
});

it('opens each news article on its own page in a new tab', function () {
    makeNews(['title' => 'Linked News', 'slug' => 'linked-news']);

    $this->get('/news')
        ->assertOk()
        ->assertSee('href="'.route('news.show', 'linked-news').'" target="_blank" rel="noopener"', false)
        ->assertDontSee('href="#"', false);
});

// article page
it('shows a published article with its title, date and body', function () {
    makeNews([
        'title' => 'Readable Article',
        'slug' => 'readable-article',
        'body' => "Line one.\n<script>alert(1)</script>",
        'published_at' => '2026-09-15',
    ]);

    $this->get('/news/readable-article')
        ->assertOk()
        ->assertSee('Readable Article')
        ->assertSee('September 15, 2026')
        ->assertSee('Line one.')
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertDontSee('vol-promo-section', false);

    // The volunteer sign-up section stays on the News list only.
    $this->get('/news')->assertSee('vol-promo-section', false);
});

it('returns 404 for draft, future-dated and unknown articles', function () {
    makeNews(['slug' => 'a-draft', 'is_published' => false, 'published_at' => null]);
    makeNews(['slug' => 'coming-soon', 'published_at' => now()->addWeek()]);

    $this->get('/news/a-draft')->assertNotFound();
    $this->get('/news/coming-soon')->assertNotFound();
    $this->get('/news/does-not-exist')->assertNotFound();
});

it('lists other published articles under More News', function () {
    makeNews(['title' => 'Main Story', 'slug' => 'main-story']);
    makeNews(['title' => 'Another Story', 'slug' => 'another-story']);

    $this->get('/news/main-story')
        ->assertOk()
        ->assertSee('More News')
        ->assertSee('Another Story');
});

it('redirects the old /news/our-story URL to /our-story', function () {
    $this->get('/news/our-story')->assertRedirect('/our-story')->assertStatus(301);
    $this->get('/our-story')->assertOk();
});
