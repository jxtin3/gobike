@extends('admin.operations.layout')
@section('title', 'Patients')
@section('heading', 'Patient records')
@section('subheading', 'Check-up records collected by GoBikers during their rondas.')

@section('content')
@php
    $hasQuery = filled(request('search')) || filled(request('barangay'));
@endphp

<div class="toolbar">
    <form class="search-form" role="search" method="GET" action="{{ route('admin.operations.patients.index') }}">
        <input class="input" type="search" name="search" value="{{ request('search') }}" placeholder="Search by patient name" aria-label="Search patients">
        <select class="input" name="barangay" aria-label="Filter by barangay" onchange="this.form.submit()">
            <option value="">All barangays</option>
            @foreach ($barangays as $b)
                <option value="{{ $b }}" @selected(request('barangay') === $b)>{{ $b }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-secondary"><x-admin.icon name="search" /> Search</button>
    </form>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Patient</th>
                <th>Barangay</th>
                <th>Vitals</th>
                <th>Recorded by</th>
                <th>Date</th>
                <th><span class="sr-only">Actions</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($patients as $patient)
                @php
                    $initials = collect(preg_split('/\s+/', trim($patient->name)))->filter()->take(2)
                        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
                    $highBp = $patient->sys >= 140 || $patient->dia >= 90;
                    $highTemp = $patient->temp >= 37.5;
                @endphp
                <tr>
                    <td>
                        <div class="cell-user">
                            <span class="avatar" aria-hidden="true">{{ $initials }}</span>
                            <div>
                                <b>{{ $patient->name }}</b>
                                <small>{{ $patient->age }} yrs old</small>
                            </div>
                        </div>
                    </td>
                    <td>{{ $patient->barangay ?: '—' }}</td>
                    <td>
                        BP {{ $patient->sys }}/{{ $patient->dia }} · Pulse {{ $patient->pulse }}
                        <small class="muted" style="display:block">Temp {{ rtrim(rtrim(number_format($patient->temp, 1), '0'), '.') }} °C</small>
                        @if ($highBp) <span class="pill pill-warn">Elevated BP</span> @endif
                        @if ($highTemp) <span class="pill pill-warn">Elevated temp</span> @endif
                    </td>
                    <td>{{ $patient->recorder?->name ?? '—' }}</td>
                    <td>
                        {{ $patient->recorded_at->format('M j, Y') }}
                        <small class="muted" style="display:block">{{ $patient->recorded_at->format('g:i A') }}</small>
                    </td>
                    <td>
                        <div class="row-actions">
                            <a class="icon-btn" href="{{ route('admin.operations.patients.show', $patient) }}" title="View" aria-label="View {{ $patient->name }}"><x-admin.icon name="eye" /></a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr class="is-static">
                    <td colspan="6">
                        <div class="empty">
                            <x-admin.icon name="activity" />
                            @if ($hasQuery)
                                <b>No patients match your search</b>
                                <p>Try a different name, or clear the filters.</p>
                                <a class="btn btn-secondary btn-sm" href="{{ route('admin.operations.patients.index') }}">Clear filters</a>
                            @else
                                <b>No patient records yet</b>
                                <p>Records appear here after GoBikers save them during a ronda in the mobile app.</p>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $patients->links('admin.partials.pagination') }}
@endsection