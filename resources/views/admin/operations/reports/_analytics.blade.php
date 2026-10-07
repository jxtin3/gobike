<section class="analytics-dashboard" data-analytics-dashboard data-entrance="{{ !empty($dashboardEntrance) ? 'true' : 'false' }}" data-checkups='@json($checkups)' data-pie='@json($pie)' data-vitals='@json($vitals)'>
    @unless (request()->routeIs('admin.dashboard'))
    <form class="report-filter analytics-filter" method="GET" action="{{ request()->routeIs('admin.dashboard') ? route('admin.dashboard') : route('admin.operations.reports.index') }}">
        <div class="analytics-filter-fields">
            <label>
                <span>Month</span>
                <input class="input" type="month" name="month" value="{{ $selectedMonth }}" required>
            </label>
            <button class="btn btn-secondary" type="submit">Apply</button>
        </div>
    </form>
    @endunless

    <div class="analytics-overview">
        <div class="analytics-kpis" role="group" aria-label="Choose a vital sign">
            @foreach ($vitals as $key => $vital)
                <button
                    class="analytics-kpi"
                    type="button"
                    data-kpi="{{ $key }}"
                    aria-pressed="false"
                >
                    <span class="analytics-kpi-top">
                        <span>{{ $vital['label'] }}</span>
                        <x-admin.icon :name="match ($key) {
                            'blood_pressure' => 'activity',
                            'pulse' => 'heart',
                            'respiration' => 'wind',
                            default => 'thermometer',
                        }" />
                    </span>
                    <strong><span data-kpi-high="{{ $vital['totalHigh'] }}">{{ number_format($vital['totalHigh']) }}</span><small>high</small></strong>
                    <span class="analytics-kpi-foot"><span><span data-kpi-low="{{ $vital['totalLow'] }}">{{ number_format($vital['totalLow']) }}</span> low</span><span>{{ $year }}</span></span>
                </button>
            @endforeach
        </div>

        <section class="analytics-chart-card analytics-chart-pie" aria-labelledby="analytics-pie-title">
            <header class="analytics-chart-header">
                <div>
                    <span class="analytics-eyebrow">Vital signs share</span>
                    <h2 id="analytics-pie-title">Abnormal readings by vital sign</h2>
                </div>
                <span class="analytics-chart-total"><span data-pie-total>0</span> total</span>
            </header>
            <div class="analytics-pie-content">
                <div class="analytics-pie-legend" data-pie-legend></div>
                <div class="analytics-pie-chart" data-pie-chart role="img" aria-label="Abnormal readings by vital sign"></div>
            </div>
        </section>
    </div>

    <div class="analytics-visuals analytics-vitals-layout">
        <section class="analytics-chart-card analytics-chart-primary" aria-labelledby="analytics-selected-title">
            <header class="analytics-chart-header">
                <div>
                    <span class="analytics-eyebrow">Monthly activity</span>
                    <h2 id="analytics-selected-title">Patient check-ups</h2>
                </div>
                <span class="analytics-chart-total">{{ number_format($checkupsTotal) }} total</span>
            </header>
            <div class="analytics-chart-canvas analytics-time-chart" data-bar-chart role="img" aria-label="Monthly patient check-ups bar chart"></div>
            <p class="analytics-chart-note">Monthly records in the selected period</p>
        </section>

        <section class="analytics-chart-card analytics-chart-primary" aria-labelledby="analytics-trend-title">
            <header class="analytics-chart-header">
                <div>
                    <span class="analytics-eyebrow">Vital-sign screening</span>
                    <h2 id="analytics-trend-title" data-trend-title>Blood pressure</h2>
                </div>
                <span class="analytics-trend-key">
                    <i class="analytics-key-high"></i>High
                    <i class="analytics-key-low"></i>Low
                </span>
            </header>
            <div class="analytics-chart-canvas analytics-time-chart" data-line-chart role="img" aria-label="Monthly high and low vital sign screening chart"></div>
        </section>

    </div>
</section>

@push('scripts')
    <script defer src="{{ asset('js/admin/reports.js') }}?v={{ filemtime(public_path('js/admin/reports.js')) }}"></script>
@endpush
