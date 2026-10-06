<section class="analytics-dashboard" data-analytics-dashboard data-entrance="{{ !empty($dashboardEntrance) ? 'true' : 'false' }}" data-metrics='@json($metrics)'>
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

    <div class="analytics-kpis" role="group" aria-label="Choose a key performance indicator">
        @foreach ($metrics as $metric)
            <button
                class="analytics-kpi"
                type="button"
                data-kpi="{{ $metric['key'] }}"
                aria-pressed="false"
            >
                <span class="analytics-kpi-top">
                    <span>{{ $metric['label'] }}</span>
                    <x-admin.icon :name="match ($metric['key']) {
                        'patients' => 'activity',
                        'gobikers' => 'users',
                        'donations' => 'heart',
                        'volunteers' => 'user-check',
                        'contact_messages', 'gobiker_messages' => 'message-square',
                        'news' => 'file-text',
                        default => 'image',
                    }" />
                </span>
                <strong data-kpi-total="{{ $metric['total'] }}">{{ number_format($metric['total']) }}</strong>
                <span class="analytics-kpi-foot">{{ $year }} total</span>
            </button>
        @endforeach
    </div>

    <div class="analytics-visuals">
        <section class="analytics-chart-card analytics-chart-primary" aria-labelledby="analytics-selected-title">
            <header class="analytics-chart-header">
                <div>
                    <span class="analytics-eyebrow">Monthly activity</span>
                    <h2 id="analytics-selected-title" data-chart-title>Patient check-ups</h2>
                </div>
                <span class="analytics-chart-total" data-chart-total>0 total</span>
            </header>
            <div class="analytics-chart-canvas analytics-time-chart" data-bar-chart role="img" aria-label="Monthly bar chart"></div>
            <p class="analytics-chart-note">Monthly records in the selected period</p>
        </section>

        <section class="analytics-chart-card analytics-chart-primary" aria-labelledby="analytics-trend-title">
            <header class="analytics-chart-header">
                <div>
                    <span class="analytics-eyebrow">Activity trend</span>
                    <h2 id="analytics-trend-title" data-trend-title>Patient check-ups</h2>
                </div>
                <span class="analytics-trend-key"><i></i> Monthly total</span>
            </header>
            <div class="analytics-chart-canvas analytics-time-chart" data-line-chart role="img" aria-label="Monthly trend line chart"></div>
            <p class="analytics-chart-note">Trend for the selected KPI</p>
        </section>

        <section class="analytics-chart-card analytics-chart-pie" aria-labelledby="analytics-pie-title">
            <header class="analytics-chart-header">
                <div>
                    <span class="analytics-eyebrow">Period breakdown</span>
                    <h2 id="analytics-pie-title">By month</h2>
                </div>
            </header>
            <div class="analytics-pie-content">
                <div class="analytics-pie-chart" data-pie-chart role="img" aria-label="Selected KPI split by month"></div>
                <div class="analytics-pie-legend" data-pie-legend></div>
            </div>
            <p class="analytics-chart-note">Share of the selected KPI by month</p>
        </section>
    </div>
</section>

@push('scripts')
    <script defer src="{{ asset('js/admin/reports.js') }}?v={{ filemtime(public_path('js/admin/reports.js')) }}"></script>
@endpush
