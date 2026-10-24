@extends('admin.operations.layout')
@section('title', 'Patient records')
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
        <a class="btn btn-secondary btn-sm" href="{{ route('admin.operations.patients.index', array_filter(['search' => request('search'), 'barangay' => request('barangay')])) }}" data-skeleton="rows">Clear</a>
    </div>
@endif

<x-admin.filters :action="route('admin.operations.patients.index')" placeholder="Search by patient name" label="Search patients"
                 :hidden="$period ? ['year' => request('year'), 'month' => request('month')] : []" :filtered="filled(request('search')) || filled(request('barangay')) || $reading">
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
</x-admin.filters>

<div data-list>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Patient</th>
                <th>Barangay</th>
                <th>Vitals</th>
                <th>Follow-up</th>
                <th>Recorded by</th>
                <th>Date</th>
                <th><span class="sr-only">Actions</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($patients as $patient)
                @php
                    $abnormal = $patient->abnormalReadings();
                @endphp
                <tr>
                    <td>
                        <div class="cell-user">
                            <x-admin.avatar :name="$patient->name" />
                            <div>
                                <b><a class="cell-link" href="{{ route('admin.operations.patients.show', $patient) }}" data-skeleton="detail">{{ $patient->name }}</a></b>
                                <small>{{ $patient->age }} yrs</small>
                            </div>
                        </div>
                    </td>
                    <td>{{ $patient->barangay ?: '—' }}</td>
                    <td>
                        BP {{ $patient->sys }}/{{ $patient->dia }} · Pulse {{ $patient->pulse }}
                        <small class="sub-line">Temp {{ rtrim(rtrim(number_format($patient->temp, 1), '0'), '.') }} °C{{ $patient->blood_sugar !== null ? ' · Sugar '.$patient->blood_sugar.' mg/dL' : '' }}</small>
                        @foreach ($abnormal as $key => $label)
                            <span class="pill {{ str_ends_with($key, '_high') ? 'pill-warn' : 'pill-info' }}">{{ $label }}</span>
                        @endforeach
                    </td>
                    <td>
                        @switch($followUps[$patient->id] ?? null)
                            @case('stop') <span class="pill pill-ok">Stopped</span> @break
                            @case('continue') <span class="pill pill-info">Keep checking</span> @break
                            @case('can_stop') <span class="pill pill-neutral" title="Normal on the last {{ \App\Models\Patient::NORMAL_CHECKUPS_TO_STOP }} check-ups">Can stop?</span> @break
                            @default <span class="muted">—</span>
                        @endswitch
                    </td>
                    <td>{{ $patient->recorder?->name ?? '—' }}</td>
                    <td>
                        {{ $patient->recorded_at->format('M j, Y') }}
                        <small class="sub-line">{{ $patient->recorded_at->format('g:i A') }}</small>
                    </td>
                    <td>
                        <div class="row-actions">
                            <a class="icon-btn" href="{{ route('admin.operations.patients.show', $patient) }}" title="View" aria-label="View {{ $patient->name }}" data-skeleton="detail"><x-admin.icon name="eye" /></a>
                        </div>
                    </td>
                </tr>
            @empty
                @if ($hasQuery)
                    <x-admin.empty :row="7" icon="activity" title="No patients match these filters" :clear="route('admin.operations.patients.index')">
                        Try a different name, or clear the filters.
                    </x-admin.empty>
                @else
                    <x-admin.empty :row="7" icon="activity" title="No patient records yet">
                        Records appear here after GoBikers save them during a ronda in the mobile app.
                    </x-admin.empty>
                @endif
            @endforelse
        </tbody>
    </table>
</div>

    <x-admin.pager :paginator="$patients" noun="patient" />
</div>
@endsection