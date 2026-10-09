<?php
use App\Models\News;
use App\Models\User;

// news
it('news page',
function () {
        $response = $this->get('/news');
        $response->assertStatus(200);
});

it('allows admins to edit the publication date of news', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $news = News::create([
        'title' => 'Editable News',
        'slug' => 'editable-news',
        'body' => 'News body',
        'is_published' => true,
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

it('lets admins choose and order the three homepage news stories', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $stories = collect(['First choice', 'Second choice', 'Third choice'])->map(
        fn ($title, $index) => News::create([
            'title' => $title,
            'slug' => 'homepage-choice-'.$index,
            'body' => 'Story body',
            'is_published' => true,
            'published_at' => now()->subDays($index),
        ])
    );

    $this->actingAs($admin)
        ->get(route('admin.operations.news.homepage'))
        ->assertOk()
        ->assertSee('Homepage story 1')
        ->assertSee('Save homepage stories');

    $orderedStories = [$stories[2], $stories[0], $stories[1]];

    $this->put(route('admin.operations.news.homepage.update'), [
        'news' => array_map(fn (News $story) => $story->id, $orderedStories),
    ])->assertRedirect(route('admin.operations.news.homepage'))
        ->assertSessionHasNoErrors();

    expect($orderedStories[0]->fresh()->homepage_position)->toBe(1)
        ->and($orderedStories[1]->fresh()->homepage_position)->toBe(2)
        ->and($orderedStories[2]->fresh()->homepage_position)->toBe(3);

    $this->get('/')
        ->assertOk()
        ->assertSeeInOrder(['Third choice', 'First choice', 'Second choice']);
});
// $response->assertRedirect('if what is route'); -- use this if assertStatus got eeror


// published news
it('shows published news but hides draft news',
function () {
    News::create([
        'title' => 'Published Test News',
        'slug' => 'publlished-test-news',
        'body' => 'This is a published test article',
        'is_published' => true,
        'published_at' => now(),
    ]);
    
    News::create([
        'title' => 'Draft Test News',
        'slug' => 'draft-test-news',
        'body' => 'This is a draft test article',
        'is_published' => false,
        'published_at' => null,
    ]);

    $response = $this->get('/news');

    $response->assertStatus(200)
        ->assertSee('Published Test News')
        ->assertDontSee('Draft Test News');
        
});
