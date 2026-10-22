@extends('admin.operations.layout')
@section('title', $gobiker->name.' — GoBiker report')
@section('heading', $gobiker->name)
@section('subheading', 'GoBiker report · '.$period->label())
@section('back', route('admin.operations.gobiker-reports.index', $period->query()))
@section('back_label', 'Back to GoBiker reports')
@section('actions')
    <button type="button" class="btn btn-secondary" data-print><x-admin.icon name="printer" /> Print</button>
    <a class="btn btn-primary" href="{{ route('admin.operations.gobiker-reports.show', [$gobiker, ...$period->query(['format' => 'csv'])]) }}"><x-admin.icon name="download" /> Download CSV</a>
@endsection

@use('App\Services\GobikerActivity')

@section('content')
@include('admin.partials.print-head', ['title' => 'GoBiker report: '.$gobiker->name, 'subtitle' => $period->label()])

@include('admin.operations.gobiker-reports._period', ['route' => 'admin.operations.gobiker-reports.show', 'params' => [$gobiker]])

<p class="report-meta">
    <span><x-admin.icon name="map" /> {{ $gobiker->barangay ?: 'No barangay' }}</span>
    @if ($gobiker->mobile)
        <span><x-admin.icon name="phone-call" /> <a href="tel:{{ $gobiker->mobile }}">{{ $gobiker->mobile }}</a></span>
    @endif
    <span><x-admin.icon name="mail" /> <a href="mailto:{{ $gobiker->email }}">{{ $gobiker->email }}</a></span>
</p>

@include('admin.operations.gobiker-reports._tracking-note')

<div class="stat-row">
    <div class="stat-card">
        <span class="stat-icon"><x-admin.icon name="navigation" /></span>
        <div>
            <div class="stat-num">{{ number_format($report['rondas']) }}</div>
            <div class="stat-label">{{ Str::plural('Ronda', $report['rondas']) }} · {{ GobikerActivity::formatMinutes($report['minutes']) }}</div>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon"><x-admin.icon name="activity" /></span>
        <div>
            <div class="stat-num">{{ number_format($report['checkups']) }}</div>
            <div class="stat-label">Check-ups recorded</div>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon"><x-admin.icon name="user-check" /></span>
        <div>
            <div class="stat-num">{{ number_format($report['people']) }}</div>
            <div class="stat-label">People checked</div>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon t-warn"><x-admin.icon name="alert-triangle" /></span>
        <div>
            <div class="stat-num">{{ number_format($report['abnormal']) }}</div>
            <div class="stat-label">Check-ups with abnormal readings</div>
        </div>
    </div>
</div>

<section class="report-section">
    <h2>{{ $period->type === 'week' ? 'Day by day' : 'Week by week' }}</h2>
    @php $maxCheckups = max(1, (int) $report['breakdown']->max('checkups')); @endphp
    <div class="table-wrap">
        <table class="report-table">
            <thead>
                <tr>
                    <th>{{ $period->type === 'week' ? 'Day' : 'Week' }}</th>
                    <th class="num">Rondas</th>
                    <th>Check-ups</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['breakdown'] as $bucket)
                    <tr @class(['is-static', 'is-idle' => ! $bucket['rondas'] && ! $bucket['checkups']])>
                        <td>{{ $bucket['label'] }}</td>
                        <td class="num">{{ $bucket['rondas'] }}</td>
                        <td>
                            <span class="meter-cell">
                                <b>{{ $bucket['checkups'] }}</b>
                                <span class="meter" aria-hidden="true"><i style="width: {{ round($bucket['checkups'] / $maxCheckups * 100) }}%"></i></span>
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

<section class="report-section">
    <h2>Rondas <span class="muted">({{ $report['rondaList']->count() }})</span></h2>
    <div class="table-wrap">
        <table class="report-table">
            <thead>
                <tr>
                    <th>Started</th>
                    <th>Ended</th>
                    <th class="num">Duration</th>
                    <th>Barangay</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['rondaList'] as $ronda)
                    <tr class="is-static">
                        <td>{{ $ronda->started_at->format('D, M j · g:i A') }}</td>
                        <td>
                            @if ($ronda->ended_at)
                                {{ $ronda->ended_at->format($ronda->ended_at->isSameDay($ronda->started_at) ? 'g:i A' : 'D, M j · g:i A') }}
                            @else
                                <span class="pill pill-neutral">Not stopped</span>
                            @endif
                        </td>
                        <td class="num">{{ GobikerActivity::formatMinutes($ronda->durationMinutes() ?? 0) }}</td>
                        <td>{{ $ronda->barangay ?: '—' }}</td>
                    </tr>
                @empty
                    <tr class="is-static"><td colspan="4" class="muted">No rondas recorded in {{ $period->label() }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="report-section">
    <h2>Check-ups <span class="muted">({{ $report['checkupList']->count() }})</span></h2>
    <div class="table-wrap">
        <table class="report-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Patient</th>
                    <th>BP</th>
                    <th class="num">Pulse</th>
                    <th class="num">Resp.</th>
                    <th class="num">Temp</th>
                    <th>Readings</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['checkupList'] as $checkup)
                    <tr class="is-static">
                        <td>{{ $checkup->recorded_at->format('M j · g:i A') }}</td>
                        <td>
                            <a class="cell-link" href="{{ route('admin.operations.patients.show', $checkup) }}"><b>{{ $checkup->name }}</b></a>
                            <small class="sub-line">{{ $checkup->barangay ?: '—' }}</small>
                        </td>
                        <td>{{ $checkup->sys }}/{{ $checkup->dia }}</td>
                        <td class="num">{{ $checkup->pulse }}</td>
                        <td class="num">{{ $checkup->resp }}</td>
                        <td class="num">{{ rtrim(rtrim(number_format($checkup->temp, 1), '0'), '.') }} °C</td>
                        <td>
                            @forelse ($checkup->abnormalReadings() as $key => $label)
                                <span class="pill {{ str_ends_with($key, '_high') ? 'pill-warn' : 'pill-info' }}">{{ $label }}</span>
                            @empty
                                <span class="pill pill-ok">Normal</span>
                            @endforelse
                        </td>
                    </tr>
                @empty
                    <tr class="is-static"><td colspan="7" class="muted">No check-ups recorded in {{ $period->label() }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
