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

it('displays the three permanent news stories on the homepage', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Youth Responders Mobilize for Community Health Missions')
        ->assertSee('Empowering Local Responders with Life-Saving Skills')
        ->assertSee('Strengthening Community Disaster Response with LGUs');
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
