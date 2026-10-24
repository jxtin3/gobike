@extends('admin.operations.layout')
@section('title', 'Volunteer applications')
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

<x-admin.filters :action="route('admin.operations.volunteers.index')" placeholder="Search name, email, barangay or town" label="Search volunteer applications" :filtered="$hasQuery">
    <select class="input" name="status" aria-label="Filter by status" data-autosubmit>
        <option value="">All statuses</option>
        @foreach (VolunteerApplication::STATUSES as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>
</x-admin.filters>

<div data-list>
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
            <tr>
                <td>
                    <div class="cell-user">
                        <x-admin.avatar :name="$app->full_name" />
                        <div>
                            <b><a class="cell-link" href="{{ route('admin.operations.volunteers.show', $app) }}" data-skeleton="detail">{{ $app->full_name }}</a></b>
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
                        <a class="icon-btn" href="{{ route('admin.operations.volunteers.show', $app) }}" title="View" aria-label="View {{ $app->full_name }}'s application" data-skeleton="detail"><x-admin.icon name="eye" /></a>
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
                @if ($hasQuery)
                    <x-admin.empty :row="7" icon="users" title="No applications match" :clear="route('admin.operations.volunteers.index')">
                        Try a different search, or clear the filters.
                    </x-admin.empty>
                @else
                    <x-admin.empty :row="7" icon="users" title="No volunteer applications yet">
                        Applications submitted through the volunteer sign-up form will appear here.
                    </x-admin.empty>
                @endif
            @endforelse
        </tbody>
    </table>
</div>

    <x-admin.pager :paginator="$applications" noun="application" />
</div>
@endsection
