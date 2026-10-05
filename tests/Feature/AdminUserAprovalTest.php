<?php

use App\Models\User;

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