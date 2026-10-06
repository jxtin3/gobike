@extends('admin.operations.layout')
@section('title', 'Reports')
@section('heading', 'Reports & analytics')
@section('subheading', 'Monthly, aggregated trends across activity recorded in the system.')

@section('content')
<form class="report-filter" method="GET" action="{{ route('admin.operations.reports.index') }}">
    <label>
        <span>From month</span>
        <input class="input" type="month" name="from" value="{{ $fromMonth }}" required>
    </label>
    <label>
        <span>To month</span>
        <input class="input" type="month" name="to" value="{{ $toMonth }}" required>
    </label>
    <button class="btn btn-secondary" type="submit"><x-admin.icon name="bar-chart" /> Apply range</button>
</form>

<p class="report-privacy muted">Reports show totals only. Patient names, contact details, and other personal information are not included.</p>

<div class="report-grid">
    @foreach ($charts as $chart)
        <section class="report-card" aria-labelledby="report-title-{{ $loop->index }}">
            <header class="report-card-header">
                <div>
                    <h2 id="report-title-{{ $loop->index }}">{{ $chart['label'] }}</h2>
                    <p>Total in selected range</p>
                </div>
                <strong>{{ number_format($chart['total']) }}</strong>
            </header>

            <div class="report-chart-scroll">
                <div class="report-chart" role="img" aria-label="{{ $chart['label'] }} by month; total {{ number_format($chart['total']) }}">
                    @foreach ($chart['months'] as $month)
                        <div class="report-chart-column" title="{{ $month['label'] }}: {{ number_format($month['count']) }}">
                            <span class="report-chart-value">{{ $month['count'] ?: '' }}</span>
                            <div class="report-chart-track">
                                <div class="report-chart-bar" style="height: {{ $month['height'] }}%"></div>
                            </div>
                            <span class="report-chart-month">{{ $month['shortLabel'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            <p class="report-chart-summary">
                @if ($chart['total'] === 0)
                    No records in this date range.
                @else
                    {{ number_format($chart['total']) }} records across {{ count($chart['months']) }} months.
                @endif
            </p>
        </section>
    @endforeach
</div>
@endsection
