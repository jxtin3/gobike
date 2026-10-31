<?php

use App\Models\TrainingRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(fn () => Storage::fake('local'));

/** The ID photo every training request now needs. */
function trainingId(array $overrides = []): array
{
    return $overrides + ['id_documents' => [UploadedFile::fake()->image('school-id.jpg', 600, 400)]];
}

it('sends the visitor back to a thank-you note after a training request', function () {
    $this->from('/what-we-do')
        ->post(route('training-request.store'), [
            'name' => 'Lito Garcia',
            'email' => 'lito@example.com',
            'phone' => '09181234567',
            'address' => 'Purok 3, Barangay Poblacion',
            'tier' => 'standard',
            'preferred_date' => now()->addDays(3)->toDateString(),
            'preferred_window' => 'afternoon',
        ] + trainingId())
        ->assertRedirect(url('/what-we-do').'#training-thanks');

    expect(TrainingRequest::where('email', 'lito@example.com')->exists())->toBeTrue();

    $this->get('/what-we-do')
        ->assertOk()
        ->assertSee('id="training-thanks"', false)
        ->assertSee('Thank you, Lito!')
        ->assertSee('Standard (₱1,500)')
        ->assertSee(now()->addDays(3)->format('D, M j, Y').' · Afternoon')
        ->assertSee('What happens next');
});

it('reopens the form with an error when no plan was chosen', function () {
    $this->from('/what-we-do')
        ->post(route('training-request.store'), [
            'name' => 'Lito Garcia',
            'email' => 'lito@example.com',
            'address' => 'Purok 3, Barangay Poblacion',
        ])
        ->assertRedirect('/what-we-do');

    $this->get('/what-we-do')
        ->assertOk()
        ->assertSee('Please choose a training plan above');
});

it('offers two paid plans and no free plan, starting on a date the person picks', function () {
    $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class); // several posts in a row
    $this->get('/what-we-do')->assertOk()
        ->assertSee('₱1,500')
        ->assertSee('₱2,500')
        ->assertDontSee("price: 'Free'", false)
        ->assertSee('name="preferred_date"', false)
        ->assertSee(route('volunteer'), false);

    $base = trainingId([
        'name' => 'Lito Garcia', 'email' => 'lito@example.com', 'phone' => '09181234567', 'address' => 'Purok 3',
        'preferred_date' => now()->addDays(3)->toDateString(), 'preferred_window' => 'morning',
    ]);

    // The free plan is gone, and a date in the past or too far ahead is refused.
    $this->from('/what-we-do')->post(route('training-request.store'), $base + ['tier' => 'basic'])->assertSessionHasErrors('tier');
    $this->from('/what-we-do')->post(route('training-request.store'), ['tier' => 'standard', 'preferred_date' => now()->toDateString()] + $base)->assertSessionHasErrors('preferred_date');
    $this->from('/what-we-do')->post(route('training-request.store'), ['tier' => 'standard', 'preferred_date' => now()->addDays(61)->toDateString()] + $base)->assertSessionHasErrors('preferred_date');
    $this->from('/what-we-do')->post(route('training-request.store'), ['tier' => 'standard', 'preferred_date' => null] + $base)->assertSessionHasErrors('preferred_date');
    expect(TrainingRequest::count())->toBe(0);

    // The phone number is needed to confirm the date.
    $this->from('/what-we-do')->post(route('training-request.store'), ['tier' => 'standard', 'phone' => ''] + $base)->assertSessionHasErrors('phone');
    expect(TrainingRequest::count())->toBe(0);

    $this->from('/what-we-do')->post(route('training-request.store'), $base + ['tier' => 'advance'])->assertSessionHasNoErrors();
    expect(TrainingRequest::first())->tier->toBe('advance')->preferred_window->toBe('morning');

    // The time of day is optional.
    $this->from('/what-we-do')->post(route('training-request.store'), ['tier' => 'standard', 'preferred_window' => null] + $base)->assertSessionHasNoErrors();
    expect(TrainingRequest::latest('id')->first()->schedule_label)->toBe(now()->addDays(3)->format('D, M j, Y'));
});

it('needs an ID with the request and keeps it private', function () {
    $this->withoutMiddleware(ThrottleRequests::class); // several posts in a row
    $base = [
        'name' => 'Lito Garcia', 'email' => 'lito@example.com', 'phone' => '09181234567', 'address' => 'Purok 3',
        'preferred_date' => now()->addDays(3)->toDateString(), 'tier' => 'standard',
    ];

    // No ID, a wrong file type, an oversized file and too many files are all refused.
    $this->from('/what-we-do')->post(route('training-request.store'), $base)->assertSessionHasErrors('id_documents');
    $this->from('/what-we-do')->post(route('training-request.store'), $base + ['id_documents' => [UploadedFile::fake()->create('virus.exe', 10)]])->assertSessionHasErrors('id_documents.0');
    $this->from('/what-we-do')->post(route('training-request.store'), $base + ['id_documents' => [UploadedFile::fake()->create('huge.pdf', 6000, 'application/pdf')]])->assertSessionHasErrors('id_documents.0');
    $this->from('/what-we-do')->post(route('training-request.store'), $base + ['id_documents' => [
        ...array_map(fn ($i) => UploadedFile::fake()->image("id-{$i}.jpg"), range(1, TrainingRequest::MAX_ID_FILES + 1)),
    ]])->assertSessionHasErrors('id_documents');
    expect(TrainingRequest::count())->toBe(0);

    // A front and a back are stored on the private disk.
    $this->from('/what-we-do')->post(route('training-request.store'), $base + ['id_documents' => [
        UploadedFile::fake()->image('front.jpg'), UploadedFile::fake()->create('back.pdf', 100, 'application/pdf'),
    ]])->assertSessionHasNoErrors();
    $request = TrainingRequest::first();
    expect($request->id_paths)->toHaveCount(2)->and($request->id_paths[0])->toStartWith('training-ids/');
    Storage::disk('local')->assertExists($request->id_paths[0]);

    // Only a signed-in admin can open it, and deleting the request deletes the files.
    $this->get(route('admin.operations.training-requests.id', [$request, 0]))->assertRedirect();
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin)->get(route('admin.operations.training-requests.index'))
        ->assertSee('id="idViewer"', false)
        ->assertSee('data-id-img0="'.route('admin.operations.training-requests.id', [$request, 0]).'"', false)
        ->assertSee('data-id-url1="'.route('admin.operations.training-requests.id', [$request, 1]).'"', false)
        ->assertSee('data-id-pdf1="1"', false); // the PDF back gets a file tile, not a picture
    $this->actingAs($admin)->get(route('admin.operations.training-requests.id', [$request, 0]))->assertOk();
    $this->actingAs($admin)->get(route('admin.operations.training-requests.id', [$request, 2]))->assertNotFound();
    $paths = $request->id_paths;
    $this->actingAs($admin)->delete(route('admin.operations.training-requests.destroy', $request));
    Storage::disk('local')->assertMissing($paths[0]);
    Storage::disk('local')->assertMissing($paths[1]);
});
