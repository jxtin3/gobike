@extends('admin.operations.layout')
@section('title', 'Volunteers')
@section('heading', 'Volunteer applications')
@section('subheading', 'All volunteer sign-up submissions from the Join the Movement page.')

@use('App\Models\VolunteerApplication')

@section('content')
@php
    $hasQuery = filled(request('search')) || filled(request('status'));
@endphp

{{-- Summary Cards --}}
<div class="stat-row">
    <div class="stat-card">
        <span class="stat-icon"><x-admin.icon name="users" /></span>
        <div>
            <div class="stat-num">{{ number_format($totalCount) }}</div>
            <div class="stat-label">Total applications</div>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon"><x-admin.icon name="clock" /></span>
        <div>
            <div class="stat-num">{{ number_format($pendingCount) }}</div>
            <div class="stat-label">Pending review</div>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon"><x-admin.icon name="user-check" /></span>
        <div>
            <div class="stat-num">{{ number_format($approvedCount) }}</div>
            <div class="stat-label">Approved</div>
        </div>
    </div>
</div>

{{-- Toolbar --}}
<div class="toolbar">
    <form class="search-form" role="search" method="GET" action="{{ route('admin.operations.volunteers.index') }}">
        <input class="input" type="search" name="search" value="{{ request('search') }}" placeholder="Search name, email, barangay or town" aria-label="Search volunteer applications">
        <select class="input" name="status" aria-label="Filter by status" data-autosubmit>
            <option value="">All statuses</option>
            @foreach (VolunteerApplication::STATUSES as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-secondary"><x-admin.icon name="search" /> Search</button>
    </form>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Applicant</th>
                <th>Age</th>
                <th>Location</th>
                <th>Availability</th>
                <th>Status</th>
                <th>Submitted</th>
                <th><span class="sr-only">Actions</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($applications as $app)
            @php
                $initials = collect(preg_split('/\s+/', $app->full_name))->filter()->take(2)
                    ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
            @endphp
            <tr>
                <td>
                    <div class="cell-user">
                        <span class="avatar" aria-hidden="true">{{ $initials }}</span>
                        <div>
                            <b><a class="cell-link" href="{{ route('admin.operations.volunteers.show', $app) }}">{{ $app->full_name }}</a></b>
                            <small>{{ $app->email }}</small>
                        </div>
                    </div>
                </td>
                <td>{{ $app->age }} yrs</td>
                <td>
                    {{ $app->barangay }}
                    <small class="sub-line">{{ $app->municipality }}, {{ $app->province }}</small>
                </td>
                <td>{{ ucfirst($app->availability) }}</td>
                <td><span class="pill pill-{{ $app->status_pill }}">{{ $app->status_label }}</span></td>
                <td>
                    {{ $app->created_at->format('M j, Y') }}
                    <small class="sub-line">{{ $app->created_at->format('g:i A') }}</small>
                </td>
                <td>
                    <div class="row-actions">
                        <a class="icon-btn" href="{{ route('admin.operations.volunteers.show', $app) }}" title="View" aria-label="View {{ $app->full_name }}'s application"><x-admin.icon name="eye" /></a>
                        <form method="POST" action="{{ route('admin.operations.volunteers.destroy', $app) }}"
                              data-confirm-title="Delete this application?"
                              data-confirm-text="{{ $app->full_name }}'s application, including emergency and guardian contacts, will be permanently deleted."
                              data-confirm-ok="Delete application">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="icon-btn danger" title="Delete" aria-label="Delete {{ $app->full_name }}'s application"><x-admin.icon name="trash" /></button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr class="is-static">
                <td colspan="7">
                    <div class="empty">
                        <x-admin.icon name="users" />
                        @if ($hasQuery)
                            <b>No applications match</b>
                            <p>Try a different search, or clear the filters.</p>
                            <a class="btn btn-secondary btn-sm" href="{{ route('admin.operations.volunteers.index') }}">Clear filters</a>
                        @else
                            <b>No volunteer applications yet</b>
                            <p>Applications submitted through the volunteer sign-up form will appear here.</p>
                        @endif
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $applications->links('admin.partials.pagination') }}
@endsection
