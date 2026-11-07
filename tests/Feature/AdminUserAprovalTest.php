<?php

use App\Mail\GoBikerApproved;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

function pendingGoBiker(): User
{
    return User::factory()->create(['role' => 'GoBiker', 'status' => 'Inactive', 'is_admin' => false]);
}

// admin approve the gobiker sign up
it('lets an admin approve a pending GoBiker', function () {
    $admin = User::factory()->create(['is_admin' => true, 'role' => 'Admin']);
    $biker = pendingGoBiker();

    $this->actingAs($admin)->patch(route('admin.operations.users.approve', $biker))->assertRedirect();

    expect($biker->fresh()->status)->toBe('Active');
});

it('removes a declined sign-up', function () {
    $admin = User::factory()->create(['is_admin' => true, 'role' => 'Admin']);
    $biker = pendingGoBiker();

    $this->actingAs($admin)->delete(route('admin.operations.users.decline', $biker))->assertRedirect();

    expect(User::find($biker->id))->toBeNull();
});

it('blocks non-admins from approving', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->patch(route('admin.operations.users.approve', pendingGoBiker()))->assertForbidden();
});

function approvalAdmin(): User
{
    return User::factory()->create(['is_admin' => true, 'role' => 'Admin']);
}

it('emails the GoBiker when an admin approves them', function () {
    Mail::fake();
    $biker = pendingGoBiker();

    $this->actingAs(approvalAdmin())->patch(route('admin.operations.users.approve', $biker))
        ->assertSessionHas('success');

    Mail::assertSent(GoBikerApproved::class, fn ($mail) => $mail->hasTo($biker->email));
    $biker->refresh();
    expect($biker->approved_at)->not->toBeNull()
        ->and($biker->approval_emailed_at)->not->toBeNull();
});

it('still approves a GoBiker whose email cannot be sent, and says so', function () {
    Mail::fake();
    $biker = User::factory()->create(['role' => 'GoBiker', 'status' => 'Inactive', 'email' => 'gobiker1@demo.rondago.test']);

    $this->actingAs(approvalAdmin())->patch(route('admin.operations.users.approve', $biker))
        ->assertSessionHas('error');

    Mail::assertNothingSent();
    expect($biker->fresh()->status)->toBe('Active')
        ->and($biker->fresh()->approval_emailed_at)->toBeNull();
});

it('approves every pending sign-up at once and emails each one', function () {
    Mail::fake();
    $bikers = collect(range(1, 3))->map(fn () => pendingGoBiker());

    $this->actingAs(approvalAdmin())->patch(route('admin.operations.users.approve-all'))
        ->assertSessionHas('success');

    expect(User::pendingApproval()->count())->toBe(0);
    Mail::assertSent(GoBikerApproved::class, 3);
    $bikers->each(fn ($b) => expect($b->fresh()->approval_emailed_at)->not->toBeNull());
});

it('emails only approved GoBikers who have not had the email, never older accounts', function () {
    Mail::fake();
    $waiting = User::factory()->create(['role' => 'GoBiker', 'status' => 'Active', 'approved_at' => now()]);
    $alreadyEmailed = User::factory()->create(['role' => 'GoBiker', 'status' => 'Active', 'approved_at' => now(), 'approval_emailed_at' => now()]);
    $approvedBefore = User::factory()->create(['role' => 'GoBiker', 'status' => 'Active']);
    $demo = User::factory()->create(['role' => 'GoBiker', 'status' => 'Active', 'approved_at' => now(), 'email' => 'x@demo.rondago.test']);

    $this->actingAs(approvalAdmin())->post(route('admin.operations.users.email-approved'))
        ->assertSessionHas('success');

    Mail::assertSent(GoBikerApproved::class, 1);
    Mail::assertSent(GoBikerApproved::class, fn ($mail) => $mail->hasTo($waiting->email));
    expect($approvedBefore->fresh()->approval_emailed_at)->toBeNull()
        ->and($demo->fresh()->approval_emailed_at)->toBeNull();
});

it('resends one GoBiker their approval email', function () {
    Mail::fake();
    $biker = User::factory()->create(['role' => 'GoBiker', 'status' => 'Active']);

    $this->actingAs(approvalAdmin())->post(route('admin.operations.users.email-approval', $biker))
        ->assertSessionHas('success');

    Mail::assertSent(GoBikerApproved::class, fn ($mail) => $mail->hasTo($biker->email));
});

it('blocks non-admins from the bulk approval actions', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->patch(route('admin.operations.users.approve-all'))->assertForbidden();
    $this->actingAs($user)->post(route('admin.operations.users.email-approved'))->assertForbidden();
});

it('shows the bulk approval and email buttons on the users page', function () {
    pendingGoBiker();
    pendingGoBiker();
    User::factory()->create(['role' => 'GoBiker', 'status' => 'Active', 'approved_at' => now()]);

    $this->actingAs(approvalAdmin())->get(route('admin.operations.users.index', ['filter' => 'pending']))
        ->assertOk()
        ->assertSee('Approve all (2)')
        ->assertSee('Email them');
});
