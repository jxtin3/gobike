<?php

use App\Models\GobikerMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows a dedicated reader for GoBiker messages in the admin inbox', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $sender = User::factory()->create([
        'name' => 'Test GoBiker',
        'barangay' => 'San Isidro',
    ]);
    $message = GobikerMessage::create([
        'user_id' => $sender->id,
        'message' => 'We need medical supplies for our next community ride.',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.operations.gobiker-messages.index'))
        ->assertOk()
        ->assertSee('All messages')
        ->assertSee('Unread')
        ->assertSee('Read message')
        ->assertSee('id="gobikerMessageReader"', false)
        ->assertSee('We need medical supplies for our next community ride.')
        ->assertSee('1', false);
});

it('marks a GoBiker message as read', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $sender = User::factory()->create();
    $message = GobikerMessage::create([
        'user_id' => $sender->id,
        'message' => 'A message for the admin team.',
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.operations.gobiker-messages.mark-read', $message))
        ->assertOk()
        ->assertJson(['is_read' => true]);

    expect($message->fresh()->is_read)->toBeTrue();
});

it('filters read and unread messages from their inbox summary cards', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $sender = User::factory()->create();
    GobikerMessage::create([
        'user_id' => $sender->id,
        'message' => 'Read message shown in the read filter.',
        'is_read' => true,
    ]);
    GobikerMessage::create([
        'user_id' => $sender->id,
        'message' => 'Unread message shown in the unread filter.',
        'is_read' => false,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.operations.gobiker-messages.index', ['status' => 'read']))
        ->assertOk()
        ->assertSee('Read message shown in the read filter.')
        ->assertDontSee('Unread message shown in the unread filter.')
        ->assertSee('aria-current="page"', false);

    $this->get(route('admin.operations.gobiker-messages.index', ['status' => 'unread']))
        ->assertOk()
        ->assertSee('Unread message shown in the unread filter.')
        ->assertDontSee('Read message shown in the read filter.')
        ->assertSee('aria-current="page"', false);

    $this->get(route('admin.operations.gobiker-messages.index'))
        ->assertOk()
        ->assertSee('Read message shown in the read filter.')
        ->assertSee('Unread message shown in the unread filter.');
});
