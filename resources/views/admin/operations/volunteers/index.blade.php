@extends('admin.operations.layout')
@section('title', 'Volunteer applications')
@section('heading', 'Volunteer applications')
@section('subheading', 'Volunteer sign-ups. Training is free and given per class: once 20 to 25 are waiting, schedule a class and call them.')

@use('App\Models\VolunteerApplication')

@section('content')
@php
    $hasQuery = filled(request('search')) || filled(request('status')) || filled(request('class'));
    $count = fn ($status) => (int) ($counts[$status] ?? 0);
    $nextClassSize = min($waitingCount, $classMax);
@endphp

{{-- Summary Cards --}}
<div class="stat-row">
    <div class="stat-card">
        <span class="stat-icon"><x-admin.icon name="inbox" /></span>
        <div>
            <div class="stat-num">{{ number_format($count('pending')) }}</div>
            <div class="stat-label">New to review</div>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon"><x-admin.icon name="clock" /></span>
        <div>
            <div class="stat-num">{{ number_format($waitingCount) }} <small class="muted">of {{ $classMin }}</small></div>
            <div class="stat-label">Waiting for a class</div>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon"><x-admin.icon name="users" /></span>
        <div>
            <div class="stat-num">{{ number_format($count('scheduled')) }}</div>
            <div class="stat-label">In a scheduled class</div>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon"><x-admin.icon name="user-check" /></span>
        <div>
            <div class="stat-num">{{ number_format($count('trained')) }}</div>
            <div class="stat-label">Trained</div>
        </div>
    </div>
</div>

@if ($waitingCount >= $classMin)
    <div class="callout">
        <x-admin.icon name="users" />
        <div>
            <b>{{ $waitingCount }} volunteers are waiting: enough for a class</b>
            <span>Schedule a class below. The {{ $nextClassSize }} who waited longest are added; then call them with the date and venue.</span>
        </div>
        <a class="btn btn-secondary btn-sm" href="#schedule-class">Schedule a class</a>
    </div>
@endif

{{-- Training classes --}}
<section class="detail-columns vol-classes">
    <form class="detail-card vol-schedule" id="schedule-class" method="POST" action="{{ route('admin.operations.volunteers.classes.store') }}"
          data-confirm-title="Schedule this class?"
          data-confirm-text="The {{ $nextClassSize }} volunteers who waited longest will be put in this class."
          data-confirm-ok="Schedule class">
        @csrf
        <h2 class="detail-card-title">Schedule a class</h2>
        <p class="vol-schedule__intro">
            Volunteers are trained together, {{ $classMin }}–{{ $classMax }} per class.
            <b>{{ $waitingCount }} waiting now.</b>
        </p>
        <div class="grid-2">
            <div class="field">
                <label for="class-date">Class date</label>
                <input class="input @error('starts_on') is-invalid @enderror" id="class-date" type="date" name="starts_on" value="{{ old('starts_on') }}"
                       min="{{ now()->addDay()->toDateString() }}" required aria-describedby="starts_on-error">
                <x-admin.field-error name="starts_on" />
            </div>
            <div class="field">
                <label for="class-venue">Venue</label>
                <input class="input @error('venue') is-invalid @enderror" id="class-venue" type="text" name="venue" value="{{ old('venue') }}"
                       maxlength="150" required placeholder="e.g. Bugallon Municipal Hall" aria-describedby="venue-error">
                <x-admin.field-error name="venue" />
            </div>
        </div>
        <div class="field">
            <label for="class-notes">Notes <span class="muted">(optional)</span></label>
            <textarea class="input" id="class-notes" name="notes" rows="2" maxlength="1000" placeholder="e.g. 8:00 AM to 5:00 PM, bring a valid ID">{{ old('notes') }}</textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <x-admin.icon name="users" /> Schedule class{{ $waitingCount > 0 ? ' for '.$nextClassSize : '' }}
            </button>
            @if ($waitingCount === 0)
                <span class="muted vol-schedule__hint">Mark new applications as "Waiting for class" first.</span>
            @endif
        </div>
    </form>

    <article class="detail-card">
        <h2 class="detail-card-title">Classes</h2>
        @forelse ($classes as $class)
            <div class="vol-class-row">
                <div>
                    <a href="{{ route('admin.operations.volunteers.index', ['class' => $class->id]) }}"><b>{{ $class->starts_on->format('D, M j, Y') }}</b></a>
                    <small class="sub-line">{{ $class->venue }} · {{ $class->applications_count }} {{ \Illuminate\Support\Str::plural('volunteer', $class->applications_count) }}</small>
                </div>
                @if ($class->isCompleted())
                    <span class="pill pill-ok">Done</span>
                @else
                    <form method="POST" action="{{ route('admin.operations.volunteers.classes.complete', $class) }}"
                          data-confirm-title="Mark this class as done?"
                          data-confirm-text="Everyone in the class on {{ $class->starts_on->format('M j') }} will be marked as trained."
                          data-confirm-ok="Mark as done">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-secondary btn-sm"><x-admin.icon name="check" /> Mark trained</button>
                    </form>
                @endif
            </div>
        @empty
            <p class="muted">No classes yet. Schedule the first one when {{ $classMin }} volunteers are waiting.</p>
        @endforelse
    </article>
</section>

<x-admin.filters :action="route('admin.operations.volunteers.index')" placeholder="Search name, email, mobile, barangay or town" label="Search volunteer applications" :filtered="$hasQuery">
    <select class="input" name="status" aria-label="Filter by status" data-autosubmit>
        <option value="">All statuses</option>
        @foreach (VolunteerApplication::STATUS_LABELS as $status => $label)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ $label }}</option>
        @endforeach
    </select>
    <select class="input" name="class" aria-label="Filter by class" data-autosubmit>
        <option value="">All classes</option>
        @foreach ($classes as $class)
            <option value="{{ $class->id }}" @selected((int) request('class') === $class->id)>{{ $class->starts_on->format('M j, Y') }} · {{ $class->venue }}</option>
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
            <tr data-href="{{ route('admin.operations.volunteers.show', $app) }}">
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
                <td>
                    <span class="pill pill-{{ $app->status_pill }}">{{ $app->status_label }}</span>
                    @if ($app->volunteerClass)
                        <small class="sub-line">{{ $app->volunteerClass->starts_on->format('M j, Y') }}</small>
                    @endif
                </td>
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
