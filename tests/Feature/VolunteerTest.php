<?php
use App\Models\User;
use App\Models\VolunteerApplication;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** The identity proof every application must include. */
function volunteerIdFields(array $overrides = []): array
{
    return array_merge([
        'idType' => 'school-id',
        'idDocuments' => [UploadedFile::fake()->image('school-id.jpg', 600, 400)],
    ], $overrides);
}

beforeEach(fn () => Storage::fake('local'));

// volunteer
it('volunteer page',
function () {
        $response = $this->get('/volunteer');
        $response->assertStatus(200);
});

// store volunteer page
it('stores a valid volunteer application', function () {
    $response = $this->postJson('/volunteer', [
        'firstName' => 'Test',
        'lastName' => 'Volunteer',
        'email' => 'volunteer@example.com',
        'phone' => '09123456789',
        'age' => 18,
        'status' => 'student',
        'school' => 'Test University',
        'barangay' => 'Test Barangay',
        'municipality' => 'Test Municipality',
        'province' => 'Test Province',
        'availability' => 'weekends',
        'motivation' => 'I want to help the community.',
        'emergencyName' => 'Emergency Contact',
        'emergencyPhone' => '09987654321',
        'agree' => true,
    ] + volunteerIdFields());

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Application received.',
        ]);

    $this->assertDatabaseHas('volunteer_applications', [
        'id_type' => 'school-id',
        'first_name' => 'Test',
        'last_name' => 'Volunteer',
        'email' => 'volunteer@example.com',
        'age' => 18,
        'occupation_status' => 'student',
        'barangay' => 'Test Barangay',
        'municipality' => 'Test Municipality',
        'province' => 'Test Province',
        'availability' => 'weekends',
        'emergency_name' => 'Emergency Contact',
        'emergency_phone' => '09987654321',
    ]);
});

// minor volunteer test
it('requires guardian information for a minor volunteer', function () {
    $response = $this->postJson('/volunteer', [
        'firstName' => 'Minor',
        'lastName' => 'Volunteer',
        'email' => 'minor@example.com',
        'phone' => '09123456789',
        'age' => 17,
        'status' => 'student',
        'school' => 'Test High School',
        'barangay' => 'Test Barangay',
        'municipality' => 'Test Municipality',
        'province' => 'Test Province',
        'availability' => 'weekends',
        'motivation' => 'I want to help.',
        'emergencyName' => 'Emergency Contact',
        'emergencyPhone' => '09987654321',
        'agree' => true,
    ] + volunteerIdFields());

    $response->assertStatus(422)
        ->assertJsonValidationErrors([
            'guardianName',
            'guardianPhone',
            'guardianConsent',
        ]);
});


// store guardian information with minor (<18)
it('stores a valid minor volunteer application with guardian information', function () {
    $response = $this->postJson('/volunteer', [
        'firstName' => 'Minor',
        'lastName' => 'Volunteer',
        'email' => 'minor@example.com',
        'phone' => '09123456789',
        'age' => 17,
        'status' => 'student',
        'school' => 'Test High School',
        'barangay' => 'Test Barangay',
        'municipality' => 'Test Municipality',
        'province' => 'Test Province',
        'availability' => 'weekends',
        'motivation' => 'I want to help the community.',
        'emergencyName' => 'Emergency Contact',
        'emergencyPhone' => '09987654321',

        'guardianName' => 'Parent Guardian',
        'guardianPhone' => '09876543210',
        'guardianConsent' => true,

        'agree' => true,
    ] + volunteerIdFields(['idType' => 'barangay-clearance']));

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Application received.',
        ]);

    $this->assertDatabaseHas('volunteer_applications', [
        'first_name' => 'Minor',
        'last_name' => 'Volunteer',
        'age' => 17,
        'guardian_name' => 'Parent Guardian',
        'guardian_phone' => '09876543210',
        'guardian_consent' => true,
    ]);
});

// reject a volunteer with no require fields
it('rejects a volunteer application with missing required fields', function () {
    $response = $this->postJson('/volunteer', []);

    $response->assertStatus(422);

    $errors = $response->json('errors');

    expect($errors)->toHaveKeys([
        'firstName',
        'lastName',
        'email',
        'phone',
        'age',
        'status',
        'barangay',
        'municipality',
        'province',
        'availability',
        'emergencyName',
        'emergencyPhone',
        'idType',
        'idDocuments',
        'agree',
    ]);
});

it('keeps the uploaded ID on the private disk, never in a public folder', function () {
    $this->postJson('/volunteer', [
        'firstName' => 'Ana', 'lastName' => 'Reyes', 'email' => 'ana@example.com', 'phone' => '09123456789',
        'age' => 19, 'status' => 'student', 'barangay' => 'Poblacion', 'municipality' => 'Bugallon',
        'province' => 'Pangasinan', 'availability' => 'weekends', 'emergencyName' => 'Mom',
        'emergencyPhone' => '09987654321', 'agree' => true,
    ] + volunteerIdFields(['idDocuments' => [UploadedFile::fake()->create('clearance.pdf', 200, 'application/pdf')], 'idType' => 'residency']))
        ->assertOk();

    $application = VolunteerApplication::firstWhere('email', 'ana@example.com');

    expect($application->id_type)->toBe('residency')
        ->and($application->id_type_label)->toBe('Certificate of residency')
        ->and($application->id_paths)->toHaveCount(1)
        ->and($application->id_paths[0])->toStartWith('volunteer-ids/');
    Storage::disk('local')->assertExists($application->id_paths[0]);
    Storage::disk('public')->assertMissing($application->id_paths[0]);
});

it('rejects an ID that is the wrong kind of file, too big, or an unknown document type', function () {
    $base = [
        'firstName' => 'Ana', 'lastName' => 'Reyes', 'email' => 'ana@example.com', 'phone' => '09123456789',
        'age' => 19, 'status' => 'student', 'barangay' => 'Poblacion', 'municipality' => 'Bugallon',
        'province' => 'Pangasinan', 'availability' => 'weekends', 'emergencyName' => 'Mom',
        'emergencyPhone' => '09987654321', 'agree' => true,
    ];

    $this->postJson('/volunteer', $base + volunteerIdFields(['idDocuments' => [UploadedFile::fake()->create('virus.exe', 10)]]))
        ->assertJsonValidationErrors('idDocuments.0');
    $this->postJson('/volunteer', $base + volunteerIdFields(['idDocuments' => [UploadedFile::fake()->create('huge.pdf', 6000, 'application/pdf')]]))
        ->assertJsonValidationErrors('idDocuments.0');
    $this->postJson('/volunteer', $base + volunteerIdFields(['idType' => 'library-card']))
        ->assertJsonValidationErrors('idType');

    expect(VolunteerApplication::count())->toBe(0);
});

it('lets only a signed-in admin open an uploaded ID, and deletes the file with the application', function () {
    Storage::disk('local')->put('volunteer-ids/proof.jpg', 'fake-image');
    Storage::disk('local')->put('volunteer-ids/proof-back.jpg', 'fake-image-back');
    $application = VolunteerApplication::create([
        'first_name' => 'Ana', 'last_name' => 'Reyes', 'email' => 'ana@example.com', 'phone' => '09170000000',
        'age' => 19, 'occupation_status' => 'student', 'barangay' => 'Poblacion', 'municipality' => 'Bugallon',
        'province' => 'Pangasinan', 'availability' => 'weekends', 'emergency_name' => 'Mom', 'emergency_phone' => '09170000001',
        'id_type' => 'school-id', 'id_paths' => ['volunteer-ids/proof.jpg', 'volunteer-ids/proof-back.jpg'],
    ]);

    auth()->logout();
    $this->get(route('admin.operations.volunteers.id', [$application, 0]))->assertRedirect();

    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin)->get(route('admin.operations.volunteers.show', $application))
        ->assertOk()->assertSee('School ID')->assertSee('View document 1 larger')->assertSee('View document 2 larger')
        ->assertSee(route('admin.operations.volunteers.id', [$application, 0]), false)
        ->assertSee('id="idViewer"', false);
    $this->actingAs($admin)->get(route('admin.operations.volunteers.id', [$application, 0]))->assertOk();
    $this->actingAs($admin)->get(route('admin.operations.volunteers.id', [$application, 1]))->assertOk();
    $this->actingAs($admin)->get(route('admin.operations.volunteers.id', [$application, 2]))->assertNotFound();

    $this->actingAs($admin)->delete(route('admin.operations.volunteers.destroy', $application));
    Storage::disk('local')->assertMissing('volunteer-ids/proof.jpg');
    Storage::disk('local')->assertMissing('volunteer-ids/proof-back.jpg');
});

it('accepts several ID files at once, but no more than five', function () {
    $base = [
        'firstName' => 'Ana', 'lastName' => 'Reyes', 'email' => 'ana@example.com', 'phone' => '09123456789',
        'age' => 19, 'status' => 'student', 'barangay' => 'Poblacion', 'municipality' => 'Bugallon',
        'province' => 'Pangasinan', 'availability' => 'weekends', 'emergencyName' => 'Mom',
        'emergencyPhone' => '09987654321', 'agree' => true,
    ];

    $this->postJson('/volunteer', $base + volunteerIdFields(['idDocuments' => [
        UploadedFile::fake()->image('front.jpg', 600, 400),
        UploadedFile::fake()->image('back.png', 600, 400),
        UploadedFile::fake()->create('clearance.pdf', 200, 'application/pdf'),
    ]]))->assertOk();

    $application = VolunteerApplication::firstWhere('email', 'ana@example.com');
    expect($application->id_paths)->toHaveCount(3);
    foreach ($application->id_paths as $path) {
        Storage::disk('local')->assertExists($path);
    }

    $tooMany = array_map(fn ($i) => UploadedFile::fake()->image("id-{$i}.jpg", 300, 200), range(1, 6));
    $this->postJson('/volunteer', array_merge($base, ['email' => 'many@example.com']) + volunteerIdFields(['idDocuments' => $tooMany]))
        ->assertJsonValidationErrors('idDocuments');
    expect(VolunteerApplication::where('email', 'many@example.com')->exists())->toBeFalse();
});
it('offers "Volunteer with us" or "Get Trained" in the home page hero', function () {
    $this->get('/')->assertOk()
        ->assertSeeInOrder(['Volunteer with us', 'or', 'Get Trained'])
        ->assertSee('href="'.route('volunteer').'"', false);
});

it('keeps "Get Training" on the other pages that used to offer volunteering', function () {
    foreach (['/about', '/org-structure', '/news'] as $page) {
        $this->get($page)->assertOk()
            ->assertSee('/what-we-do#training-plan', false)
            ->assertDontSee('href="'.url('/volunteer').'"', false);
    }
});

it('no longer asks whether the volunteer has a bicycle', function () {
    $this->get('/volunteer')->assertOk()->assertDontSee('Do you have a bicycle?');
});

it('shows how full the next class is and tells a new volunteer their place in line', function () {
    foreach (range(1, 12) as $i) {
        VolunteerApplication::create([
            'first_name' => "V{$i}", 'last_name' => 'Cruz', 'email' => "v{$i}@example.com", 'phone' => '09170000000',
            'age' => 20, 'occupation_status' => 'student', 'barangay' => 'Poblacion', 'municipality' => 'Bugallon',
            'province' => 'Pangasinan', 'availability' => 'weekends', 'emergency_name' => 'Mom', 'emergency_phone' => '09170000001',
            'application_status' => $i <= 2 ? 'pending' : 'waiting',
        ]);
    }

    $this->get('/volunteer')->assertOk()
        ->assertSee('Next volunteer class')
        ->assertSeeInOrder(['12', 'of 20 volunteers'])
        ->assertSee('Wait for a Full Class');

    $this->postJson('/volunteer', [
        'firstName' => 'Juan', 'lastName' => 'Cruz', 'email' => 'juan@example.com', 'phone' => '09123456789',
        'age' => 19, 'status' => 'working', 'barangay' => 'Poblacion', 'municipality' => 'Bugallon',
        'province' => 'Pangasinan', 'availability' => 'weekends', 'emergencyName' => 'Mom',
        'emergencyPhone' => '09987654321', 'agree' => true,
    ] + volunteerIdFields())->assertOk()->assertJson(['position' => 13, 'class_min' => 20, 'class_max' => 25]);
});
