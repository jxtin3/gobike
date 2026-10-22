<?php

use App\Models\News;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Every admin page that can be opened without a record.
dataset('admin pages', [
    'dashboard' => 'admin.dashboard',
    'live map' => 'admin.live-map.index',
    'locations' => 'admin.locations.index',
    'profile' => 'admin.profile.show',
    'profile edit' => 'admin.profile.edit',
    'homepage content' => 'admin.operations.homepage-data.index',
    'news' => 'admin.operations.news.index',
    'news create' => 'admin.operations.news.create',
    'announcements' => 'admin.operations.announcements.index',
    'announcement create' => 'admin.operations.announcements.create',
    'pictures' => 'admin.operations.pictures.index',
    'users' => 'admin.operations.users.index',
    'users create' => 'admin.operations.users.create',
    'messages' => 'admin.operations.messages.index',
    'gobiker messages' => 'admin.operations.gobiker-messages.index',
    'patients' => 'admin.operations.patients.index',
    'gobiker reports' => 'admin.operations.gobiker-reports.index',
    'donations' => 'admin.operations.donations.index',
    'training requests' => 'admin.operations.training-requests.index',
    'chapter inquiries' => 'admin.operations.chapter-inquiries.index',
    'volunteers' => 'admin.operations.volunteers.index',
    'privacy editor' => 'admin.operations.legal.privacy',
    'terms editor' => 'admin.operations.legal.terms',
    'cookies editor' => 'admin.operations.legal.cookies',
]);

it('opens for an admin', function (string $route) {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->get(route($route))->assertOk();
})->with('admin pages');

it('sends guests to the login page', function (string $route) {
    $this->get(route($route))->assertRedirect(route('login'));
})->with('admin pages');

it('is forbidden for signed-in users who are not admins', function (string $route) {
    $user = User::factory()->create(['is_admin' => false, 'role' => 'GoBiker']);

    $this->actingAs($user)->get(route($route))->assertForbidden();
})->with('admin pages');

it('signs out an admin whose account was deactivated', function () {
    $admin = User::factory()->create(['is_admin' => true, 'status' => 'Inactive']);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('no longer has the separate operations and reports pages', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->get('/admin/operations')->assertNotFound();
    $this->actingAs($admin)->get('/admin/operations/reports')->assertNotFound();
});

it('links every section from the sidebar', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $response = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();

    foreach ([
        'admin.live-map.index', 'admin.operations.news.index', 'admin.operations.announcements.index', 'admin.operations.pictures.index',
        'admin.operations.homepage-data.index', 'admin.operations.messages.index', 'admin.operations.gobiker-messages.index',
        'admin.operations.users.index', 'admin.operations.gobiker-reports.index', 'admin.operations.patients.index', 'admin.operations.volunteers.index',
        'admin.operations.donations.index', 'admin.operations.training-requests.index', 'admin.operations.chapter-inquiries.index',
        'admin.operations.legal.terms', 'admin.profile.show',
    ] as $route) {
        $response->assertSee('href="'.route($route).'"', false);
    }
});

it('renders the user and news detail and edit pages', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $gobiker = User::factory()->create(['role' => 'GoBiker', 'status' => 'Inactive', 'name' => 'Pending Rider']);
    $news = News::create(['title' => 'Ride report', 'slug' => 'ride-report', 'body' => 'Body', 'is_published' => true, 'published_at' => now()->subDay()]);

    $this->actingAs($admin)->get(route('admin.operations.users.show', $gobiker))
        ->assertOk()->assertSee('Waiting for approval')->assertSee('Patient records');
    $this->actingAs($admin)->get(route('admin.operations.users.edit', $gobiker))
        ->assertOk()->assertSee('At least 12 characters');
    $this->actingAs($admin)->get(route('admin.operations.news.show', $news))
        ->assertOk()->assertSee('View on site');
    $this->actingAs($admin)->get(route('admin.operations.news.edit', $news))
        ->assertOk()->assertSee('name="published_at"', false);
});
