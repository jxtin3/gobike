<?php

use App\Models\Picture;
use App\Models\User;
use App\Support\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake(Media::disk());
});

it('uploads a picture from the gallery uploader', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->postJson(route('admin.operations.pictures.store'), [
            'file' => UploadedFile::fake()->image('Community ride.jpg'),
        ])
        ->assertOk()
        ->assertJson(['success' => true, 'message' => '1 file uploaded.']);

    $picture = Picture::sole();
    expect($picture->title)->toBe('Community ride')
        ->and($picture->uploaded_by)->toBe($admin->id);
    Storage::disk(Media::disk())->assertExists($picture->image_path);
});

it('rejects files that are not photos or videos', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->postJson(route('admin.operations.pictures.store'), [
            'file' => UploadedFile::fake()->create('notes.pdf', 20, 'application/pdf'),
        ])
        ->assertUnprocessable();

    expect(Picture::count())->toBe(0);
});

it('keeps long file names within the title column', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->postJson(route('admin.operations.pictures.store'), [
            'file' => UploadedFile::fake()->image(str_repeat('a', 300).'.jpg'),
        ])
        ->assertOk();

    expect(mb_strlen(Picture::sole()->title))->toBeLessThanOrEqual(250);
});

it('lists and shows pictures', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $picture = Picture::create(['title' => 'Health check', 'image_path' => 'pictures/health.jpg']);

    $this->actingAs($admin)
        ->get(route('admin.operations.pictures.index'))
        ->assertOk()
        ->assertSee('Health check')
        ->assertSee('data-confirm-title="Remove this photo?"', false)
        ->assertDontSee('onsubmit=', false);

    $this->actingAs($admin)
        ->get(route('admin.operations.pictures.show', $picture))
        ->assertOk()
        ->assertSee('alt="Health check"', false);
});

it('deletes the file and returns to the gallery when a picture is removed', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    Storage::disk(Media::disk())->put('pictures/old.jpg', 'image');
    $picture = Picture::create(['title' => 'Old', 'image_path' => 'pictures/old.jpg']);

    $this->actingAs($admin)
        ->from(route('admin.operations.pictures.show', $picture))
        ->delete(route('admin.operations.pictures.destroy', $picture))
        ->assertRedirect(route('admin.operations.pictures.index'));

    $this->assertModelMissing($picture);
    Storage::disk(Media::disk())->assertMissing('pictures/old.jpg');
});

it('has no create or edit pages for pictures', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->get('/admin/operations/pictures/create')->assertNotFound();
});
