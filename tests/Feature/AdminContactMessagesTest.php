<?php

use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeContactMessage(array $overrides = []): ContactMessage
{
    return ContactMessage::create(array_merge([
        'name' => 'Ana Reyes',
        'email' => 'ana@example.com',
        'subject' => 'volunteer',
        'phone' => '09171234567',
        'message' => 'How can I help with the next ronda?',
    ], $overrides));
}

it('lists contact messages with their read status', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    makeContactMessage();
    makeContactMessage(['name' => 'Ben Cruz', 'is_read' => true]);

    $this->actingAs($admin)
        ->get(route('admin.operations.messages.index'))
        ->assertOk()
        ->assertSee('Ana Reyes')
        ->assertSee('Ben Cruz')
        ->assertSee('data-unread-count>1<', false)
        ->assertSee('Open message from Ana Reyes');
});

it('shows what a visitor wrote when they picked the "Others" topic', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    makeContactMessage(['subject' => 'others', 'other_concern' => 'Partnership with our school']);

    $this->actingAs($admin)
        ->get(route('admin.operations.messages.index'))
        ->assertOk()
        ->assertSee('data-other-concern="Partnership with our school"', false)
        ->assertSee('Their concern');
});

it('filters messages by read status', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    makeContactMessage(['name' => 'Unread Sender']);
    makeContactMessage(['name' => 'Read Sender', 'is_read' => true]);

    $this->actingAs($admin)
        ->get(route('admin.operations.messages.index', ['status' => 'unread']))
        ->assertOk()
        ->assertSee('Unread Sender')
        ->assertDontSee('Read Sender');
});

it('marks a contact message as read', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $message = makeContactMessage();

    $this->actingAs($admin)
        ->patchJson(route('admin.operations.messages.read', $message))
        ->assertOk()
        ->assertJson(['is_read' => true]);

    expect($message->fresh()->is_read)->toBeTrue();
});

it('deletes a message and returns to the same filtered page', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $message = makeContactMessage();
    $from = route('admin.operations.messages.index', ['status' => 'unread', 'search' => 'Ana']);

    $this->actingAs($admin)
        ->from($from)
        ->delete(route('admin.operations.messages.destroy', $message))
        ->assertRedirect($from)
        ->assertSessionHas('success', 'Message deleted.');

    $this->assertModelMissing($message);
});
