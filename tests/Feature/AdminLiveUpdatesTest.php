<?php

use App\Models\ContactMessage;
use App\Models\GobikerMessage;
use App\Models\User;
use App\Models\VolunteerApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

function liveUpdates(User $admin, array $after = []): array
{
    return test()->actingAs($admin, 'web')
        ->getJson(route('admin.live-updates.index', $after ? ['after' => $after] : []))
        ->assertOk()
        ->json();
}

function newVolunteer(string $first): VolunteerApplication
{
    return VolunteerApplication::create([
        'first_name' => $first,
        'last_name' => 'Mendoza',
        'email' => strtolower($first).'@example.com',
        'phone' => '09191234567',
        'age' => 21,
        'occupation_status' => 'student',
        'barangay' => 'Poblacion',
        'municipality' => 'Bugallon',
        'province' => 'Pangasinan',
        'availability' => 'weekends',
        'has_bike' => true,
        'motivation' => 'I want to help my community.',
        'emergency_name' => 'Rosa Mendoza',
        'emergency_phone' => '09201234567',
        'application_status' => 'pending',
    ]);
}

it('is only for admins', function () {
    $this->getJson(route('admin.live-updates.index'))->assertUnauthorized();

    $gobiker = User::factory()->create(['role' => 'GoBiker', 'status' => 'Active']);
    $this->actingAs($gobiker, 'web')->getJson(route('admin.live-updates.index'))->assertForbidden();
});

it('sends the sidebar counts and a cursor, but nothing "new" on the first look', function () {
    User::factory()->create(['role' => 'GoBiker', 'status' => 'Inactive']);
    ContactMessage::create(['name' => 'Ana', 'email' => 'ana@example.com', 'message' => 'Hello', 'is_read' => false]);
    newVolunteer('Carla');

    $data = liveUpdates($this->admin);

    expect($data['counts'])->toMatchArray([
        'users' => 1,
        'volunteers' => 1,
        'messages' => 1,
        'gobiker-messages' => 0,
        'appointments' => 0,
        'training' => 0,
        'chapters' => 0,
    ])
        ->and($data['cursor']['volunteers'])->toBeGreaterThan(0)
        ->and($data['new'])->toBe([]);
});

it('lists what came in after the cursor, newest first', function () {
    newVolunteer('Carla');
    $cursor = liveUpdates($this->admin)['cursor'];

    newVolunteer('Bea');
    newVolunteer('Dina');
    $gobiker = User::factory()->create(['name' => 'Juan Dela Cruz', 'role' => 'GoBiker', 'status' => 'Inactive']);
    GobikerMessage::create(['user_id' => $gobiker->id, 'message' => 'We need more first-aid kits.']);

    $data = liveUpdates($this->admin, $cursor);

    expect($data['new']['volunteers']['total'])->toBe(2)
        ->and($data['new']['volunteers']['title'])->toBe('2 new volunteer applications')
        ->and(collect($data['new']['volunteers']['items'])->pluck('name')->all())->toBe(['Dina Mendoza', 'Bea Mendoza'])
        ->and($data['new']['volunteers']['index_url'])->toBe(route('admin.operations.volunteers.index'))
        ->and($data['new']['users']['title'])->toBe('New account')
        ->and($data['new']['users']['items'][0])->toMatchArray([
            'name' => 'Juan Dela Cruz',
            'detail' => 'GoBiker, waiting for your approval',
            'url' => route('admin.operations.users.show', $gobiker),
        ])
        ->and($data['new']['gobiker-messages']['items'][0]['name'])->toBe('Juan Dela Cruz')
        ->and($data['new'])->not->toHaveKey('messages')
        ->and($data['counts']['users'])->toBe(1)
        ->and($data['counts']['gobiker-messages'])->toBe(1);

    // The next look, from the new cursor, has nothing new again.
    expect(liveUpdates($this->admin, $data['cursor'])['new'])->toBe([]);
});

it('sends at most three items per kind but counts them all', function () {
    $cursor = liveUpdates($this->admin)['cursor'];
    foreach (['Ana', 'Bea', 'Cora', 'Dina', 'Ella'] as $name) {
        ContactMessage::create(['name' => $name, 'email' => 'x@example.com', 'message' => 'Hi', 'is_read' => false]);
    }

    $new = liveUpdates($this->admin, $cursor)['new']['messages'];

    expect($new['total'])->toBe(5)
        ->and($new['items'])->toHaveCount(3)
        ->and($new['title'])->toBe('5 new messages');
});

it('ignores a cursor that is not a number', function () {
    newVolunteer('Carla');

    expect(liveUpdates($this->admin, ['volunteers' => 'abc'])['new'])->toBe([]);
});

it('loads the live-updates script on admin pages', function () {
    $this->actingAs($this->admin, 'web')
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('data-live-updates', false)
        ->assertSee(route('admin.live-updates.index'), false);
});
