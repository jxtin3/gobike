<?php

use App\Models\ContactMessage;
use App\Models\TrainingRequest;
use App\Models\User;
use App\Models\VolunteerApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function shellAdmin(): User
{
    return User::factory()->create(['is_admin' => true]);
}

it('shows four main items and three open groups by default', function () {
    $html = $this->actingAs(shellAdmin())->get(route('admin.dashboard'))->assertOk()->getContent();

    expect($html)
        ->toContain('data-group-toggle="people"')
        ->toContain('data-group-toggle="requests"')
        ->toContain('data-group-toggle="content"')
        ->not->toContain('aria-expanded="false" aria-controls="side-items-')
        ->not->toContain('class="adm-main sk-active"');

    foreach (['Dashboard', 'Live map', 'Messages', 'GoBiker messages'] as $label) {
        expect($html)->toContain('<span class="side-label">'.$label.'</span>');
    }
});

it('renders a group closed when its cookie says so, unless the current page is inside it', function () {
    $admin = shellAdmin();

    $closed = $this->actingAs($admin)->withUnencryptedCookie('gobike_admin_closed', 'people.content')
        ->get(route('admin.operations.training-requests.index'))->getContent();

    expect($closed)
        ->toContain('aria-expanded="false" aria-controls="side-items-people"')
        ->toContain('aria-expanded="false" aria-controls="side-items-content"')
        ->toContain('aria-expanded="true" aria-controls="side-items-requests"');

    // Users lives in People, so People opens even though the cookie closed it.
    $current = $this->actingAs($admin)->withUnencryptedCookie('gobike_admin_closed', 'people')
        ->get(route('admin.operations.users.index'))->getContent();

    expect($current)->toContain('aria-expanded="true" aria-controls="side-items-people"');
});

it('ignores group names it does not know', function () {
    $html = $this->actingAs(shellAdmin())->withUnencryptedCookie('gobike_admin_closed', 'nope.<script>')
        ->get(route('admin.dashboard'))->getContent();

    expect($html)->not->toContain('<script>alert')->not->toContain('aria-expanded="false" aria-controls="side-items-');
});

it('renders the collapsed sidebar from its cookie', function () {
    $admin = shellAdmin();

    expect($this->actingAs($admin)->get(route('admin.dashboard'))->getContent())->not->toMatch('/<body class="[^"]*side-rail/');

    $rail = $this->actingAs($admin)->withUnencryptedCookie('gobike_admin_rail', '1')->get(route('admin.dashboard'))->getContent();

    expect($rail)->toMatch('/<body class="[^"]*side-rail/')->toContain('aria-label="Expand sidebar"');
});

it('adds up what is waiting in a group so a closed group still shows it', function () {
    User::factory()->count(2)->create(['role' => 'GoBiker', 'status' => 'Inactive']);
    VolunteerApplication::create([
        'first_name' => 'Carla', 'last_name' => 'Mendoza', 'email' => 'c@example.com', 'phone' => '09191234567', 'age' => 16,
        'occupation_status' => 'student', 'barangay' => 'Poblacion', 'municipality' => 'Bugallon', 'province' => 'Pangasinan',
        'availability' => 'weekends', 'has_bike' => true, 'emergency_name' => 'Rosa', 'emergency_phone' => '09201234567',
        'application_status' => 'pending',
    ]);

    $html = $this->actingAs(shellAdmin())->get(route('admin.dashboard'))->getContent();

    // 2 sign-ups + 1 application; the badge never reuses data-nav-badge (tests and scripts look that up by name).
    expect($html)->toContain('data-group-badge="people"><span class="count-num">3</span>');
});

it('always says how many there are, and shows page links only when there is more than one page', function () {
    $admin = shellAdmin();
    ContactMessage::create(['name' => 'Ana', 'email' => 'a@example.com', 'subject' => 'volunteer', 'phone' => '0917', 'message' => 'Hello', 'is_read' => false]);

    $single = $this->actingAs($admin)->get(route('admin.operations.messages.index'))->getContent();
    expect($single)->toContain('1 message')->not->toContain('class="pager-links"');

    User::factory()->count(12)->create();
    $many = $this->actingAs($admin)->get(route('admin.operations.users.index'))->getContent();
    expect($many)->toContain('Showing 1–10 of 12 users')->toContain('Page 1 of 2')->toContain('rel="next"');
});

it('explains an empty list differently when nothing matches and when there is nothing yet', function () {
    $admin = shellAdmin();

    $nothingYet = $this->actingAs($admin)->get(route('admin.operations.training-requests.index'))->getContent();
    expect($nothingYet)->toContain('No training requests yet')->not->toContain('Clear filters');

    TrainingRequest::create(['name' => 'Lito', 'email' => 'l@example.com', 'phone' => '0918', 'address' => 'Purok 3', 'tier' => 'standard', 'message' => 'x', 'status' => 'pending']);

    $filtered = $this->actingAs($admin)->get(route('admin.operations.training-requests.index', ['search' => 'zzzz']))->getContent();
    expect($filtered)->toContain('No requests match')->toContain('Clear filters')->toContain('data-skeleton="rows"');
});

it('turns a success message into a toast instead of a banner that moves the page', function () {
    $html = $this->actingAs(shellAdmin())->withSession(['success' => 'User updated.'])
        ->get(route('admin.operations.users.index'))->getContent();

    expect($html)
        ->toContain('<template data-flash="success">User updated.</template>')
        ->toContain('id="toasts"')
        ->not->toContain('data-autodismiss');
});

it('lists every validation message in a summary that links to the field', function () {
    $response = $this->actingAs(shellAdmin())->from(route('admin.operations.users.create'))->followingRedirects()
        ->post(route('admin.operations.users.store'), ['name' => '', 'email' => 'nope']);

    $response->assertSee('data-error-summary', false)->assertSee('<a href="#name">', false)->assertSee('<a href="#email">', false);
});

it('ships the loading placeholders as inert templates that carry no page text', function () {
    $html = $this->actingAs(shellAdmin())->get(route('admin.operations.users.index'))->getContent();

    foreach (['head', 'rows', 'table', 'grid', 'detail', 'form', 'dashboard', 'map', 'document'] as $type) {
        expect($html)->toContain('<template data-skeleton-template="'.$type.'">');
    }

    preg_match_all('/<template data-skeleton-template.*?<\/template>/s', $html, $templates);
    expect(implode('', $templates[0]))->not->toContain('id="')->not->toMatch('/>\s*[A-Za-z]{3,}/');
});
