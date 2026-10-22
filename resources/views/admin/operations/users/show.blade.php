@extends('admin.operations.layout')
@section('title', 'User details')
@section('heading', $user->name)
@section('subheading', $user->email)
@section('back', route('admin.operations.users.index'))
@section('back_label', 'Back to users')
@section('actions')
    <a class="btn btn-primary" href="{{ route('admin.operations.users.edit', $user) }}"><x-admin.icon name="edit" /> Edit</a>
    <form method="POST" action="{{ route('admin.operations.users.destroy', $user) }}"
          data-confirm-title="Delete {{ $user->name }}?"
          data-confirm-text="This permanently removes the account and signs them out of the app. GoBikers who recorded patient check-ups cannot be deleted; change their role instead."
          data-confirm-ok="Delete user">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-soft-danger"><x-admin.icon name="trash" /> Delete</button>
    </form>
@endsection

@section('content')
@php
    $role = $user->role ?? 'User';
    $pending = $user->isPendingApproval();
    $patientCount = $user->patients()->count();
@endphp

@if ($pending)
    <div class="callout">
        <x-admin.icon name="user-check" />
        <div>
            <b>Waiting for approval</b>
            <span>This GoBiker signed up in the mobile app and cannot sign in yet.</span>
        </div>
        <form method="POST" action="{{ route('admin.operations.users.decline', $user) }}" class="callout-action"
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
        <dt>Role</dt><dd><span class="pill {{ $role === 'GoBiker' ? 'pill-info' : 'pill-neutral' }}">{{ $role }}</span></dd>
        <dt>Status</dt><dd>
            @if ($pending)
                <span class="pill pill-warn"><i class="dot"></i> Pending approval</span>
            @else
                <span class="pill {{ $user->status === 'Active' ? 'pill-ok' : 'pill-neutral' }}">{{ $user->status ?? 'Active' }}</span>
            @endif
        </dd>
        <dt>Mobile</dt><dd>@if ($user->mobile)<a href="tel:{{ $user->mobile }}">{{ $user->mobile }}</a>@else — @endif</dd>
        <dt>Barangay</dt><dd>{{ $user->barangay ?: '—' }}</dd>
        @if ($role === 'GoBiker')
            <dt>Patient records</dt><dd>{{ $patientCount ? number_format($patientCount) : 'None yet' }}</dd>
        @endif
        <dt>Joined</dt><dd>{{ $user->created_at->format('F j, Y') }}</dd>
        <dt>Last updated</dt><dd>{{ $user->updated_at->format('F j, Y') }}</dd>
    </dl>
</article>
@endsection