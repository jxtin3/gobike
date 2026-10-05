@extends('admin.operations.layout')
@section('title', 'User details')
@section('heading', $user->name)
@section('subheading', $user->email)
@section('back', route('admin.operations.users.index'))
@section('back_label', 'Users')
@section('actions')
    <a class="btn btn-secondary" href="{{ route('admin.operations.users.edit', $user) }}"><x-admin.icon name="edit" /> Edit</a>
@endsection

@section('content')
@php
    $role = $user->role ?? ($user->is_admin ? 'Admin' : 'User');
    $pending = $user->isPendingApproval();
@endphp

@if ($pending)
    <div class="callout">
        <x-admin.icon name="user-check" />
        <div>
            <b>Waiting for approval</b>
            <span>This GoBiker signed up in the mobile app and cannot sign in yet.</span>
        </div>
        <form method="POST" action="{{ route('admin.operations.users.decline', $user) }}" style="margin-left:auto"
              data-confirm-title="Decline this sign-up?"
              data-confirm-text="The request from {{ $user->name }} will be removed. They can sign up again later."
              data-confirm-ok="Decline sign-up">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-soft-danger btn-sm">Decline</button>
        </form>
        <form method="POST" action="{{ route('admin.operations.users.approve', $user) }}"
              data-confirm-title="Approve {{ $user->name }}?"
              data-confirm-text="They will be able to sign in to the GoBiker app right away."
              data-confirm-ok="Approve" data-confirm-tone="ok">
            @csrf @method('PATCH')
            <button type="submit" class="btn btn-ok btn-sm"><x-admin.icon name="check" /> Approve</button>
        </form>
    </div>
@endif

<article class="detail-panel">
    <dl class="user-details">
        <dt>Role</dt><dd>{{ $role }}</dd>
        <dt>Mobile</dt><dd>{{ $user->mobile ?: '—' }}</dd>
        <dt>Barangay</dt><dd>{{ $user->barangay ?: '—' }}</dd>
        <dt>Joined</dt><dd>{{ $user->created_at->format('F j, Y') }}</dd>
        <dt>Last updated</dt><dd>{{ $user->updated_at->format('F j, Y') }}</dd>
    </dl>
</article>
@endsection