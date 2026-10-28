@extends('admin.operations.layout')
@section('title', 'GoBiker reports')
@section('heading', 'GoBiker reports')
@section('subheading', 'How many rondas each GoBiker took and how many patients they checked.')
@section('actions')
    <button type="button" class="btn btn-secondary" data-print><x-admin.icon name="printer" /> Print</button>
    <a class="btn btn-primary" href="{{ route('admin.operations.gobiker-reports.index', $period->query(['search' => $search, 'barangay' => $barangay, 'format' => 'csv'])) }}"><x-admin.icon name="download" /> Download CSV</a>
@endsection

@section('content')
@include('admin.partials.print-head', ['title' => 'GoBiker report', 'subtitle' => $period->label()])

@include('admin.operations.gobiker-reports._period', ['route' => 'admin.operations.gobiker-reports.index', 'extra' => ['search' => $search, 'barangay' => $barangay]])

@include('admin.operations.gobiker-reports._tracking-note')

<div class="stat-row">
    <div class="stat-card">
        <span class="stat-icon"><x-admin.icon name="users" /></span>
        <div>
            <div class="stat-num">{{ number_format($totals['active']) }} <small class="muted">of {{ number_format($rows->count()) }}</small></div>
            <div class="stat-label">GoBikers active</div>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon"><x-admin.icon name="navigation" /></span>
        <div>
            <div class="stat-num">{{ number_format($totals['rondas']) }}</div>
            <div class="stat-label">Rondas · {{ \App\Services\GobikerActivity::formatMinutes($totals['minutes']) }}</div>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon"><x-admin.icon name="activity" /></span>
        <div>
            <div class="stat-num">{{ number_format($totals['checkups']) }}</div>
            <div class="stat-label">Check-ups recorded</div>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon"><x-admin.icon name="user-check" /></span>
        <div>
            <div class="stat-num">{{ number_format($totals['people']) }}</div>
            <div class="stat-label">People checked</div>
        </div>
    </div>
</div>

<x-admin.filters :action="route('admin.operations.gobiker-reports.index')" placeholder="Search GoBiker by name" label="Search GoBikers"
                 :hidden="['period' => $period->type, 'date' => $period->from->toDateString()]" :filtered="filled($search) || filled($barangay)">
    <select class="input" name="barangay" aria-label="Filter by barangay" data-autosubmit>
        <option value="">All barangays</option>
        @foreach ($barangays as $name)
            <option value="{{ $name }}" @selected($barangay === $name)>{{ $name }}</option>
        @endforeach
    </select>
</x-admin.filters>

@php $maxCheckups = max(1, (int) $rows->max('checkups')); @endphp
<div class="table-wrap">
    <table class="report-table">
        <thead>
            <tr>
                <th>GoBiker</th>
                <th class="num">Rondas</th>
                <th class="num">Time on ronda</th>
                <th>Check-ups</th>
                <th class="num">People checked</th>
                <th class="num">Abnormal</th>
                <th>Last ronda</th>
                <th class="no-print"><span class="sr-only">Actions</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                @php
                    $gobiker = $row['user'];
                    $url = route('admin.operations.gobiker-reports.show', [$gobiker, ...$period->query()]);
                    $initials = collect(preg_split('/\s+/', trim($gobiker->name)))->filter()->take(2)
                        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
                @endphp
                <tr @class(['is-idle' => $row['rondas'] === 0 && $row['checkups'] === 0]) data-href="{{ $url }}">
                    <td>
                        <div class="cell-user">
                            <span class="avatar" aria-hidden="true">{{ $initials }}</span>
                            <div>
                                <b><a class="cell-link" href="{{ $url }}">{{ $gobiker->name }}</a></b>
                                <small>{{ $gobiker->barangay ?: 'No barangay' }}</small>
                            </div>
                        </div>
                    </td>
                    <td class="num">{{ number_format($row['rondas']) }}</td>
                    <td class="num">{{ \App\Services\GobikerActivity::formatMinutes($row['minutes']) }}</td>
                    <td>
                        <span class="meter-cell">
                            <b>{{ number_format($row['checkups']) }}</b>
                            <span class="meter" aria-hidden="true"><i style="width: {{ round($row['checkups'] / $maxCheckups * 100) }}%"></i></span>
                        </span>
                    </td>
                    <td class="num">{{ number_format($row['people']) }}</td>
                    <td class="num">
                        @if ($row['abnormal'])
                            <span class="pill pill-warn">{{ number_format($row['abnormal']) }}</span>
                        @else
                            <span class="muted">0</span>
                        @endif
                    </td>
                    <td>{{ $row['lastRonda']?->format('M j, g:i A') ?? '—' }}</td>
                    <td class="no-print">
                        <div class="row-actions">
                            <a class="icon-btn" href="{{ $url }}" title="Open report" aria-label="Open the report for {{ $gobiker->name }}"><x-admin.icon name="eye" /></a>
                        </div>
                    </td>
                </tr>
            @empty
                @if ($search || $barangay)
                    <x-admin.empty :row="8" icon="users" title="No GoBikers match" :clear="route('admin.operations.gobiker-reports.index', $period->query())">
                        Try a different name or barangay.
                    </x-admin.empty>
                @else
                    <x-admin.empty :row="8" icon="users" title="No GoBikers yet">
                        Approved GoBiker accounts appear here.
                    </x-admin.empty>
                @endif
            @endforelse
        </tbody>
        @if ($rows->isNotEmpty())
            <tfoot>
                <tr>
                    <th>Total</th>
                    <th class="num">{{ number_format($totals['rondas']) }}</th>
                    <th class="num">{{ \App\Services\GobikerActivity::formatMinutes($totals['minutes']) }}</th>
                    <th>{{ number_format($totals['checkups']) }}</th>
                    <th class="num">{{ number_format($totals['people']) }}</th>
                    <th class="num">{{ number_format($totals['abnormal']) }}</th>
                    <th></th>
                    <th class="no-print"></th>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
<p class="report-footnote">People checked counts each patient once per GoBiker (same name and contact number). Abnormal = check-ups with a high or low reading.</p>
@endsection
