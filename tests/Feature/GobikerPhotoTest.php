<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(fn () => Storage::fake('public'));

function signUpFields(array $overrides = []): array
{
    return array_merge([
        'name' => 'Juan Dela Cruz',
        'email' => 'juan@example.com',
        'mobile' => '09171234567',
        'barangay' => 'Poblacion',
        'role' => 'GoBiker',
        'password' => 'secret-password',
        'photo' => UploadedFile::fake()->image('me.jpg', 600, 600),
    ], $overrides);
}

it('still registers a GoBiker from an app too old to send a photo', function () {
    // TEMPORARY, and the reason this test reads backwards: released app 1.0.14 sends no photo,
    // so refusing it here would stop every GoBiker sign-up from the app people actually have.
    // When 1.0.15 ships, put 'required_if:role,GoBiker' back in MobileAuthController@register and
    // turn this into "will not register a GoBiker without a profile photo" again.
    $this->postJson('/api/mobile/register', signUpFields(['photo' => null]))
        ->assertCreated()
        ->assertJsonPath('pending_approval', true);

    expect(User::firstWhere('email', 'juan@example.com')->photo_path)->toBeNull();
});

it('registers a GoBiker with a photo and reports its address back', function () {
    $response = $this->postJson('/api/mobile/register', signUpFields())
        ->assertCreated()
        ->assertJsonPath('pending_approval', true);

    $user = User::firstWhere('email', 'juan@example.com');

    expect($user->photo_path)->toStartWith('profile-photos/');
    Storage::disk('public')->assertExists($user->photo_path);
    expect($response->json('user.photo_url'))->toContain($user->photo_path);
});

it('rejects a photo that is not an image, or is too big', function (UploadedFile $photo) {
    $this->postJson('/api/mobile/register', signUpFields(['photo' => $photo]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('photo');
})->with([
    'a program' => fn () => UploadedFile::fake()->create('virus.exe', 20, 'application/octet-stream'),
    'a document' => fn () => UploadedFile::fake()->create('scan.pdf', 200, 'application/pdf'),
    'too large' => fn () => UploadedFile::fake()->image('huge.jpg')->size(6000),
]);

it('still lets a resident register without a photo', function () {
    $this->postJson('/api/mobile/register', signUpFields([
        'role' => 'Resident', 'email' => 'ana@example.com', 'photo' => null,
    ]))->assertCreated();

    expect(User::firstWhere('email', 'ana@example.com')->photo_path)->toBeNull();
});

it('lets a GoBiker who signed up earlier add a photo, replacing any old one', function () {
    $gobiker = User::factory()->create(['role' => 'GoBiker', 'status' => 'Active']);
    Sanctum::actingAs($gobiker);

    $this->post('/api/mobile/photo', ['photo' => UploadedFile::fake()->image('first.jpg')])->assertOk();
    $first = $gobiker->fresh()->photo_path;
    expect($first)->toStartWith('profile-photos/');

    $this->post('/api/mobile/photo', ['photo' => UploadedFile::fake()->image('second.jpg')])
        ->assertOk()
        ->assertJsonPath('user.photo_url', fn ($url) => is_string($url));

    $second = $gobiker->fresh()->photo_path;
    expect($second)->not->toBe($first);
    // The replaced file is not left behind in the bucket.
    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($second);
});

it('needs a signed-in user to change a photo', function () {
    $this->postJson('/api/mobile/photo', ['photo' => UploadedFile::fake()->image('me.jpg')])
        ->assertUnauthorized();
});

it('deletes the photo file when the account goes', function () {
    $this->postJson('/api/mobile/register', signUpFields())->assertCreated();
    $user = User::firstWhere('email', 'juan@example.com');
    $path = $user->photo_path;
    Storage::disk('public')->assertExists($path);

    // An admin declining the sign-up, or deleting the user later.
    $user->delete();

    Storage::disk('public')->assertMissing($path);
});
