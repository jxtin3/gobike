<?php

use App\Models\User;
use App\Support\Age;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(fn () => Storage::fake('public'));

function ageSignUp(array $overrides = []): array
{
    return array_merge([
        'name' => 'Ana Reyes',
        'email' => 'ana@example.com',
        'mobile' => '09171234567',
        'barangay' => 'Poblacion',
        'role' => 'Resident',
        'password' => 'secret-password',
    ], $overrides);
}

/** A birthdate for someone who is exactly this many years old today, shifted by $days. */
function bornYearsAgo(int $years, int $days = 0): string
{
    return now()->subYears($years)->addDays($days)->toDateString();
}

// ---------------------------------------------------------------------- the minimum age

it('keeps the minimum age in one place, 13 for both roles', function () {
    expect(Age::minimumFor('Resident'))->toBe(13)
        ->and(Age::minimumFor('User'))->toBe(13)
        ->and(Age::minimumFor('GoBiker'))->toBe(13);
});

it('registers someone who turns 13 today, whichever role', function (string $role) {
    $this->postJson('/api/mobile/register', ageSignUp([
        'role' => $role, 'birthdate' => bornYearsAgo(13),
    ]))->assertCreated();

    $user = User::firstWhere('email', 'ana@example.com');
    expect($user->birthdate->toDateString())->toBe(bornYearsAgo(13))
        ->and($user->age)->toBe(13);
})->with(['Resident', 'GoBiker']);

it('refuses someone whose 13th birthday is tomorrow, or who is younger', function (string $role, int $years, int $days) {
    $this->postJson('/api/mobile/register', ageSignUp([
        'role' => $role, 'birthdate' => bornYearsAgo($years, $days),
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('birthdate')
        ->assertJsonPath('errors.birthdate.0', 'You must be at least 13 years old to use RondaGo.');

    expect(User::count())->toBe(0);
})->with([
    'a resident, birthday tomorrow' => ['Resident', 13, 1],
    'a resident, 12' => ['Resident', 12, 0],
    'a GoBiker, birthday tomorrow' => ['GoBiker', 13, 1],
    'a GoBiker, 5' => ['GoBiker', 5, 0],
]);

it('refuses a birthdate that is not real', function (string $value) {
    $this->postJson('/api/mobile/register', ageSignUp(['birthdate' => $value]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('birthdate');
})->with([
    'in the future' => fn () => now()->addYear()->toDateString(),
    'before 1900' => '1899-12-31',
    'not a date' => 'last tuesday',
]);

it('still registers someone from an app too old to send a birthdate', function () {
    // TEMPORARY, like the photo: apps up to 1.0.20 send no birthdate. See MobileAuthController@register.
    $this->postJson('/api/mobile/register', ageSignUp())->assertCreated();

    $user = User::firstWhere('email', 'ana@example.com');
    expect($user->birthdate)->toBeNull()->and($user->age)->toBeNull();
});

it('reports the birthdate and the age to the app', function () {
    $response = $this->postJson('/api/mobile/register', ageSignUp(['birthdate' => bornYearsAgo(25, -1)]))
        ->assertCreated();

    expect($response->json('user.birthdate'))->toBe(bornYearsAgo(25, -1))
        ->and($response->json('user.age'))->toBe(25);
});

// ---------------------------------------------------------------------- working out the age

it('counts whole years, and the day before a birthday is still the year before', function () {
    $today = now()->startOfDay();

    expect(Age::fromBirthdate($today->copy()->subYears(30)))->toBe(30)
        ->and(Age::fromBirthdate($today->copy()->subYears(30)->addDay()))->toBe(29)
        ->and(Age::fromBirthdate($today->copy()->subYears(30)->subDay()))->toBe(30)
        ->and(Age::fromBirthdate(null))->toBeNull();
});

it('handles someone born on the 29th of February', function () {
    $user = User::factory()->create(['birthdate' => '2008-02-29']);

    $this->travelTo(now()->setDate(2026, 2, 28)->startOfDay());
    expect($user->fresh()->age)->toBe(17);

    $this->travelTo(now()->setDate(2026, 3, 1)->startOfDay());
    expect($user->fresh()->age)->toBe(18);
});

// ---------------------------------------------------------------------- adding it to an account

it('lets a resident add a birthdate, refuses a too-young one, and never wipes it', function () {
    $resident = User::factory()->create(['role' => 'User', 'status' => 'Active', 'barangay' => 'Poblacion']);
    Sanctum::actingAs($resident);
    $profile = ['name' => 'Ana Reyes', 'mobile' => '09171234567', 'barangay' => 'Poblacion'];

    $this->putJson(route('api.resident.profile.update'), $profile + ['birthdate' => bornYearsAgo(12)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('birthdate');
    expect($resident->fresh()->birthdate)->toBeNull();

    $this->putJson(route('api.resident.profile.update'), $profile + ['birthdate' => bornYearsAgo(40)])
        ->assertOk()
        ->assertJsonPath('user.age', 40);

    // A later edit that sends none (or an empty one) leaves it alone.
    $this->putJson(route('api.resident.profile.update'), $profile)->assertOk();
    $this->putJson(route('api.resident.profile.update'), $profile + ['birthdate' => null])->assertOk();
    expect($resident->fresh()->birthdate->toDateString())->toBe(bornYearsAgo(40));
});

it('lets a GoBiker add a birthdate, and refuses a too-young one', function () {
    $gobiker = User::factory()->create(['role' => 'GoBiker', 'status' => 'Active', 'barangay' => 'Poblacion']);
    Sanctum::actingAs($gobiker);
    $profile = ['mobile' => '09171234567', 'barangay' => 'Poblacion'];

    $this->putJson(route('api.gobiker.profile.update'), $profile + ['birthdate' => bornYearsAgo(10)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('birthdate');

    $this->putJson(route('api.gobiker.profile.update'), $profile + ['birthdate' => bornYearsAgo(30)])
        ->assertOk()
        ->assertJsonPath('user.age', 30);

    $this->putJson(route('api.gobiker.profile.update'), $profile)->assertOk();
    expect($gobiker->fresh()->birthdate->toDateString())->toBe(bornYearsAgo(30));
});

// ---------------------------------------------------------------------- the profile photo

it('stores a resident\'s photo at sign-up and reports its address', function () {
    $response = $this->postJson('/api/mobile/register', ageSignUp([
        'birthdate' => bornYearsAgo(20),
        'photo' => UploadedFile::fake()->image('me.jpg', 600, 600),
    ]))->assertCreated();

    $user = User::firstWhere('email', 'ana@example.com');
    expect($user->photo_path)->toStartWith('profile-photos/');
    Storage::disk('public')->assertExists($user->photo_path);
    expect($response->json('user.photo_url'))->toContain($user->photo_path);
});

it('lets a resident add a photo from their profile', function () {
    $resident = User::factory()->create(['role' => 'User', 'status' => 'Active']);
    Sanctum::actingAs($resident);

    $this->post('/api/mobile/photo', ['photo' => UploadedFile::fake()->image('me.jpg')])->assertOk();

    expect($resident->fresh()->photo_path)->toStartWith('profile-photos/');
});

it('removes a photo from the account and the disk', function () {
    $user = User::factory()->create(['role' => 'User', 'status' => 'Active']);
    Sanctum::actingAs($user);
    $this->post('/api/mobile/photo', ['photo' => UploadedFile::fake()->image('me.jpg')])->assertOk();
    $path = $user->fresh()->photo_path;
    Storage::disk('public')->assertExists($path);

    $this->deleteJson(route('api.mobile.photo.remove'))
        ->assertOk()
        ->assertJsonPath('user.photo_url', null);

    expect($user->fresh()->photo_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

it('copes with removing a photo when there is none', function () {
    Sanctum::actingAs(User::factory()->create(['role' => 'User', 'status' => 'Active']));

    $this->deleteJson(route('api.mobile.photo.remove'))->assertOk();
});

it('needs a signed-in user to remove a photo', function () {
    $this->deleteJson(route('api.mobile.photo.remove'))->assertUnauthorized();
});

// ---------------------------------------------------------------------- what the admin sees

it('shows the admin a GoBiker\'s age before they approve them', function () {
    $admin = User::factory()->create(['is_admin' => true, 'role' => 'Admin']);
    $pending = User::factory()->create([
        'role' => 'GoBiker', 'status' => 'Inactive', 'is_admin' => false, 'birthdate' => bornYearsAgo(19),
    ]);

    $this->actingAs($admin)->get(route('admin.operations.users.show', $pending))
        ->assertOk()
        ->assertSee('They are 19 years old.')
        ->assertSee('19 years old')
        ->assertSee(now()->subYears(19)->format('F j, Y'));
});

it('says so when a pending GoBiker gave no date of birth', function () {
    $admin = User::factory()->create(['is_admin' => true, 'role' => 'Admin']);
    $pending = User::factory()->create(['role' => 'GoBiker', 'status' => 'Inactive', 'is_admin' => false, 'birthdate' => null]);

    $this->actingAs($admin)->get(route('admin.operations.users.show', $pending))
        ->assertOk()
        ->assertSee('They did not give a date of birth.');
});

it('lists the age of each user', function () {
    $admin = User::factory()->create(['is_admin' => true, 'role' => 'Admin']);
    User::factory()->create(['name' => 'Grown Gobiker', 'role' => 'GoBiker', 'status' => 'Active', 'birthdate' => bornYearsAgo(34)]);

    $this->actingAs($admin)->get(route('admin.operations.users.index'))
        ->assertOk()
        ->assertSee('<th>Age</th>', false)
        ->assertSee('Grown Gobiker')
        ->assertSee('34');
});
