@extends('admin.operations.layout')
@section('title', 'User management')
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
    $searching = filled(request('search'));
    $hasQuery = $searching || $filter !== 'all';
@endphp

@if ($counts['pending'] > 0 && $filter !== 'pending')
    <div class="callout">
        <x-admin.icon name="user-check" />
        <div>
            <b>{{ $counts['pending'] }} GoBiker {{ \Illuminate\Support\Str::plural('sign-up', $counts['pending']) }} waiting for approval</b>
            <span>They cannot sign in to the mobile app until you approve them.</span>
        </div>
        <a class="btn btn-secondary btn-sm" href="{{ route('admin.operations.users.index', ['filter' => 'pending']) }}" data-skeleton="rows">Review</a>
    </div>
@endif

<nav class="tabs" aria-label="Filter users">
    @foreach ($tabs as $key => $label)
        <a href="{{ route('admin.operations.users.index', array_filter(['filter' => $key === 'all' ? null : $key, 'search' => request('search')])) }}"
           class="tab {{ $key === 'pending' && $counts['pending'] > 0 ? 'tab-warn' : '' }}"
           aria-current="{{ $filter === $key ? 'page' : 'false' }}" data-skeleton="rows">
            {{ $label }} <span class="count">{{ $counts[$key] }}</span>
        </a>
    @endforeach
</nav>

<x-admin.filters :action="route('admin.operations.users.index')" placeholder="Search by name or email" label="Search users"
                 :hidden="['filter' => $filter === 'all' ? null : $filter]" :filtered="$searching" />

<div data-list>
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
                        $role = $user->role ?? 'User';
                        $pending = $user->isPendingApproval();
                    @endphp
                    <tr @class(['is-pending' => $pending])>
                        <td>
                            <div class="cell-user">
                                <x-admin.avatar :name="$user->name" />
                                <div>
                                    <b><a class="cell-link" href="{{ route('admin.operations.users.show', $user) }}" data-skeleton="detail">{{ $user->name }}</a></b>
                                    <small>{{ $user->email }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="pill {{ $role === 'GoBiker' ? 'pill-info' : 'pill-neutral' }}">{{ $role }}</span>
                            @if ($pending)
                                <span class="pill pill-warn"><i class="dot"></i> Pending approval</span>
                            @endif
                        </td>
                        <td>
                            @if ($user->mobile || $user->barangay)
                                {{ $user->mobile ?: '—' }}
                                <small class="sub-line">{{ $user->barangay ?: 'No barangay' }}</small>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td>
                            {{ $user->created_at->format('M j, Y') }}
                            <small class="sub-line">{{ $user->created_at->diffForHumans() }}</small>
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
                                        <button type="submit" class="btn btn-danger btn-sm"><x-admin.icon name="x" /> Decline</button>
                                    </form>
                                @endif
                                <a class="icon-btn" href="{{ route('admin.operations.users.show', $user) }}" title="View" aria-label="View {{ $user->name }}" data-skeleton="detail"><x-admin.icon name="eye" /></a>
                                <a class="icon-btn" href="{{ route('admin.operations.users.edit', $user) }}" title="Edit" aria-label="Edit {{ $user->name }}" data-skeleton="form"><x-admin.icon name="edit" /></a>
                                <form method="POST" action="{{ route('admin.operations.users.destroy', $user) }}"
                                      data-confirm-title="Delete {{ $user->name }}?"
                                      data-confirm-text="This permanently removes the account and signs them out of the app. GoBikers who recorded patient check-ups cannot be deleted; change their role instead."
                                      data-confirm-ok="Delete user">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="icon-btn danger" title="Delete" aria-label="Delete {{ $user->name }}"><x-admin.icon name="trash" /></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    @if ($filter === 'pending' && ! $searching)
                        <x-admin.empty :row="5" icon="user-check" title="No sign-ups waiting">
                            New GoBiker registrations from the mobile app will appear here for approval.
                        </x-admin.empty>
                    @elseif ($hasQuery)
                        <x-admin.empty :row="5" icon="users" title="No users match these filters" :clear="route('admin.operations.users.index')">
                            Try a different name or email, or clear the filters.
                        </x-admin.empty>
                    @else
                        <x-admin.empty :row="5" icon="users" title="No users yet">
                            Add the first account to get started.
                            <x-slot:action><a class="btn btn-secondary btn-sm" href="{{ route('admin.operations.users.create') }}"><x-admin.icon name="plus" /> Add user</a></x-slot:action>
                        </x-admin.empty>
                    @endif
                @endforelse
            </tbody>
        </table>
    </div>

    <x-admin.pager :paginator="$users" noun="user" />
</div>
@endsection
