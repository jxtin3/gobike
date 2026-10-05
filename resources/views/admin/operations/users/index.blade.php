@extends('admin.operations.layout')
@section('title', 'Users')
@section('heading', 'User management')
@section('subheading', 'Manage accounts and approve GoBiker sign-ups from the mobile app.')
@section('actions')
    <a class="btn btn-primary" href="{{ route('admin.operations.users.create') }}"><x-admin.icon name="plus" /> Add user</a>
@endsection

@section('content')
@php
    $tabs = [
        'all' => 'All users',
        'pending' => 'Pending approval',
        'GoBiker' => 'GoBikers',
        'User' => 'Users',
    ];
    $hasQuery = filled(request('search')) || $filter !== 'all';
@endphp

@if ($counts['pending'] > 0 && $filter !== 'pending')
    <a class="callout" href="{{ route('admin.operations.users.index', ['filter' => 'pending']) }}">
        <x-admin.icon name="user-check" />
        <div>
            <b>{{ $counts['pending'] }} GoBiker {{ \Illuminate\Support\Str::plural('sign-up', $counts['pending']) }} waiting for approval</b>
            <span>They cannot sign in to the mobile app until you approve them.</span>
        </div>
        <span class="btn btn-secondary btn-sm">Review</span>
    </a>
@endif

<nav class="tabs" aria-label="Filter users">
    @foreach ($tabs as $key => $label)
        <a href="{{ route('admin.operations.users.index', array_filter(['filter' => $key === 'all' ? null : $key, 'search' => request('search')])) }}"
           class="tab {{ $key === 'pending' && $counts['pending'] > 0 ? 'tab-warn' : '' }}"
           aria-current="{{ $filter === $key ? 'page' : 'false' }}">
            {{ $label }} <span class="count">{{ $counts[$key] }}</span>
        </a>
    @endforeach
</nav>

<div class="toolbar">
    <form class="search" role="search" method="GET" action="{{ route('admin.operations.users.index') }}">
        @if ($filter !== 'all') <input type="hidden" name="filter" value="{{ $filter }}"> @endif
        <x-admin.icon name="search" />
        <input class="input" type="search" name="search" value="{{ request('search') }}" placeholder="Search by name or email" aria-label="Search users">
    </form>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>User</th>
                <th>Role</th>
                <th>Contact</th>
                <th>Joined</th>
                <th><span class="sr-only">Actions</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $user)
                @php
                    $role = $user->role ?? ($user->is_admin ? 'Admin' : 'User');
                    $pending = $user->isPendingApproval();
                    $isSelf = $user->is(auth()->user());
                    $initials = collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2)
                        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
                @endphp
                <tr @class(['is-pending' => $pending])>
                    <td>
                        <div class="cell-user">
                            <span class="avatar" aria-hidden="true">{{ $initials }}</span>
                            <div>
                                <b>{{ $user->name }}@if ($isSelf) <span class="muted">(you)</span>@endif</b>
                                <small>{{ $user->email }}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="pill {{ $role === 'Admin' ? 'pill-brand' : ($role === 'GoBiker' ? 'pill-info' : 'pill-neutral') }}">{{ $role }}</span>
                        @if ($pending)
                            <span class="pill pill-warn"><i class="dot"></i> Pending approval</span>
                        @endif
                    </td>
                    <td>
                        @if ($user->mobile || $user->barangay)
                            {{ $user->mobile ?: '—' }}
                            <small class="muted" style="display:block">{{ $user->barangay ?: 'No barangay' }}</small>
                        @else
                            <span class="muted">—</span>
                        @endif
                    </td>
                    <td>
                        {{ $user->created_at->format('M j, Y') }}
                        <small class="muted" style="display:block">{{ $user->created_at->diffForHumans() }}</small>
                    </td>
                    <td>
                        <div class="row-actions">
                            @if ($pending)
                                <form method="POST" action="{{ route('admin.operations.users.approve', $user) }}"
                                      data-confirm-title="Approve {{ $user->name }}?"
                                      data-confirm-text="They will be able to sign in to the GoBiker app right away."
                                      data-confirm-ok="Approve" data-confirm-tone="ok">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-ok btn-sm"><x-admin.icon name="check" /> Approve</button>
                                </form>
                                <form method="POST" action="{{ route('admin.operations.users.decline', $user) }}"
                                      data-confirm-title="Decline this sign-up?"
                                      data-confirm-text="The request from {{ $user->name }} will be removed. They can sign up again later."
                                      data-confirm-ok="Decline sign-up">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-soft-danger btn-sm">Decline</button>
                                </form>
                            @endif
                            <a class="icon-btn" href="{{ route('admin.operations.users.show', $user) }}" title="View" aria-label="View {{ $user->name }}"><x-admin.icon name="eye" /></a>
                            <a class="icon-btn" href="{{ route('admin.operations.users.edit', $user) }}" title="Edit" aria-label="Edit {{ $user->name }}"><x-admin.icon name="edit" /></a>
                            @unless ($isSelf)
                                <form method="POST" action="{{ route('admin.operations.users.destroy', $user) }}"
                                      data-confirm-title="Delete {{ $user->name }}?"
                                      data-confirm-text="This permanently removes the account and cannot be undone."
                                      data-confirm-ok="Delete user">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="icon-btn danger" title="Delete" aria-label="Delete {{ $user->name }}"><x-admin.icon name="trash" /></button>
                                </form>
                            @endunless
                        </div>
                    </td>
                </tr>
            @empty
                <tr class="is-static">
                    <td colspan="5">
                        <div class="empty">
                            <x-admin.icon name="{{ $filter === 'pending' ? 'user-check' : 'users' }}" />
                            @if ($filter === 'pending' && ! filled(request('search')))
                                <b>No sign-ups waiting</b>
                                <p>New GoBiker registrations from the mobile app will appear here for approval.</p>
                            @elseif ($hasQuery)
                                <b>No users match your search</b>
                                <p>Try a different name or email, or clear the filters.</p>
                                <a class="btn btn-secondary btn-sm" href="{{ route('admin.operations.users.index') }}">Clear filters</a>
                            @else
                                <b>No users yet</b>
                                <p>Add the first account to get started.</p>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $users->links('admin.partials.pagination') }}
@endsection