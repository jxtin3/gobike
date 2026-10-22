@extends('admin.operations.layout')
@section('title', 'Patients')
@section('heading', 'Patient records')
@section('subheading', 'Check-up records collected by GoBikers during their rondas.')

@section('content')
@php
    $hasQuery = filled(request('search')) || filled(request('barangay')) || $reading || $period;
    $summary = trim('Showing patients'
        .($reading ? ' with '.\App\Models\Patient::READINGS[$reading]['phrase'] : '')
        .($period ? ' in '.$period['label'] : ''));
@endphp

@if ($reading || $period)
    <div class="callout callout-info">
        <x-admin.icon name="activity" />
        <div>
            <b>{{ $summary }}</b>
            <span>{{ number_format($patients->total()) }} {{ Str::plural('patient', $patients->total()) }} found. {{ $reading ? 'Each patient appears once, with their latest matching check-up. ' : '' }}This is a screening hint, not a diagnosis.</span>
        </div>
        <a class="btn btn-secondary btn-sm" href="{{ route('admin.operations.patients.index', array_filter(['search' => request('search'), 'barangay' => request('barangay')])) }}">Clear</a>
    </div>
@endif

<div class="toolbar">
    <form class="search-form" role="search" method="GET" action="{{ route('admin.operations.patients.index') }}">
        @if ($period)
            <input type="hidden" name="year" value="{{ request('year') }}">
            @if (request('month')) <input type="hidden" name="month" value="{{ request('month') }}"> @endif
        @endif
        <input class="input" type="search" name="search" value="{{ request('search') }}" placeholder="Search by patient name" aria-label="Search patients">
        <select class="input" name="reading" aria-label="Filter by abnormal reading" data-autosubmit>
            <option value="">All readings</option>
            @foreach (\App\Models\Patient::READINGS as $key => $definition)
                <option value="{{ $key }}" @selected($reading === $key)>{{ $definition['label'] }}</option>
            @endforeach
        </select>
        <select class="input" name="barangay" aria-label="Filter by barangay" data-autosubmit>
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
                    $abnormal = $patient->abnormalReadings();
                @endphp
                <tr>
                    <td>
                        <div class="cell-user">
                            <span class="avatar" aria-hidden="true">{{ $initials }}</span>
                            <div>
                                <b><a class="cell-link" href="{{ route('admin.operations.patients.show', $patient) }}">{{ $patient->name }}</a></b>
                                <small>{{ $patient->age }} yrs</small>
                            </div>
                        </div>
                    </td>
                    <td>{{ $patient->barangay ?: '—' }}</td>
                    <td>
                        BP {{ $patient->sys }}/{{ $patient->dia }} · Pulse {{ $patient->pulse }}
                        <small class="sub-line">Temp {{ rtrim(rtrim(number_format($patient->temp, 1), '0'), '.') }} °C</small>
                        @foreach ($abnormal as $key => $label)
                            <span class="pill {{ str_ends_with($key, '_high') ? 'pill-warn' : 'pill-info' }}">{{ $label }}</span>
                        @endforeach
                    </td>
                    <td>{{ $patient->recorder?->name ?? '—' }}</td>
                    <td>
                        {{ $patient->recorded_at->format('M j, Y') }}
                        <small class="sub-line">{{ $patient->recorded_at->format('g:i A') }}</small>
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