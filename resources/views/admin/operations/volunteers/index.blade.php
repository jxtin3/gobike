@extends('admin.operations.layout')
@section('title', 'Volunteer Applications')
@section('heading', 'Volunteer Applications')
@section('subheading', 'All volunteer sign-up submissions from the Join the Movement page.')

@section('content')
@php
    $statusColors = [
        'pending'    => 'warn',
        'approved'   => 'ok',
        'rejected'   => 'bad',
        'waitlisted' => 'neutral',
    ];
@endphp

{{-- Summary Cards --}}
<div class="stat-row">
    <div class="stat-card">
        <span class="stat-icon"><x-admin.icon name="users" /></span>
        <div>
            <div class="stat-num">{{ $totalCount }}</div>
            <div class="stat-label">Total applications</div>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon"><x-admin.icon name="clock" /></span>
        <div>
            <div class="stat-num">{{ $pendingCount }}</div>
            <div class="stat-label">Pending review</div>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon"><x-admin.icon name="user-check" /></span>
        <div>
            <div class="stat-num">{{ $approvedCount }}</div>
            <div class="stat-label">Approved</div>
        </div>
    </div>
</div>

{{-- Toolbar --}}
<div class="toolbar">
    <form class="search-form" role="search" method="GET" action="{{ route('admin.operations.volunteers.index') }}">
        <input class="input" type="search" name="search" value="{{ request('search') }}" placeholder="Search name, email or barangay" aria-label="Search volunteer applications">
        <select class="input" name="status" aria-label="Filter by status" onchange="this.form.submit()">
            <option value="">All statuses</option>
            <option value="pending"    @selected(request('status') === 'pending')>Pending</option>
            <option value="approved"   @selected(request('status') === 'approved')>Approved</option>
            <option value="rejected"   @selected(request('status') === 'rejected')>Rejected</option>
            <option value="waitlisted" @selected(request('status') === 'waitlisted')>Waitlisted</option>
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
            @php $statusColor = $statusColors[$app->application_status] ?? 'neutral'; @endphp
            <tr>
                <td>
                    <div class="cell-user">
                        @php
                            $initials = collect(preg_split('/\s+/', trim("{$app->first_name} {$app->last_name}")))->filter()->take(2)
                                ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
                        @endphp
                        <span class="avatar" aria-hidden="true">{{ $initials }}</span>
                        <div>
                            <b>{{ $app->first_name }} {{ $app->last_name }}</b>
                            <small>{{ $app->email }}</small>
                        </div>
                    </div>
                </td>
                <td>{{ $app->age }} yrs</td>
                <td>
                    {{ $app->barangay }}
                    <small class="muted" style="display:block">{{ $app->municipality }}, {{ $app->province }}</small>
                </td>
                <td>{{ ucfirst($app->availability) }}</td>
                <td><span class="pill pill-{{ $statusColor }}">{{ ucfirst($app->application_status) }}</span></td>
                <td>
                    {{ $app->created_at->format('M j, Y') }}
                    <small class="muted" style="display:block">{{ $app->created_at->format('g:i A') }}</small>
                </td>
                <td>
                    <div class="row-actions">
                        <a class="icon-btn" href="{{ route('admin.operations.volunteers.show', $app) }}" title="View" aria-label="View {{ $app->first_name }}'s application"><x-admin.icon name="eye" /></a>
                    </div>
                </td>
            </tr>
            @empty
            <tr class="is-static">
                <td colspan="7">
                    <div class="empty">
                        <x-admin.icon name="users" />
                        <b>No volunteer applications yet</b>
                        <p>Applications submitted through the volunteer sign-up form will appear here.</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $applications->links('admin.partials.pagination') }}
@endsection
