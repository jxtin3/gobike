<section class="analytics-dashboard" data-analytics-dashboard data-period="{{ $periodLabel }}" data-entrance="{{ !empty($dashboardEntrance) ? 'true' : 'false' }}" data-checkups='@json($checkups)' data-pie='@json($pie)' data-vitals='@json($vitals)'>
    <div class="analytics-intro">
        @if ($checkupsTotal > 0)
            <p>
                GoBikers recorded <b>{{ number_format($checkupsTotal) }} {{ Str::plural('check-up', $checkupsTotal) }}</b> in {{ $periodLabel }}.
                Each card shows how many of them had a reading <b>outside the normal range</b>.
                A small number is good: it means most check-ups were normal.
                Click a card to see it month by month.
            </p>
        @else
            <p>No patient check-ups were recorded in {{ $periodLabel }} yet. The cards fill in as GoBikers record check-ups in the mobile app.</p>
        @endif

        <details class="analytics-help">
            <summary>What counts as normal, high or low?</summary>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Reading</th><th>Normal</th><th>High</th><th>Low</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($vitals as $vital)
                            <tr>
                                <td><b>{{ $vital['label'] }}</b> <small class="muted">{{ $vital['unit'] }}</small></td>
                                <td>{{ $vital['normal'] }}</td>
                                <td>{{ $vital['high_short'] }}</td>
                                <td>{{ $vital['low_short'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="analytics-help-note">These are screening hints for the RHU team, not a diagnosis. A check-up can have more than one abnormal reading, for example both a high blood pressure and a fast pulse.</p>
        </details>
    </div>

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
                    @php
                        $share = $checkupsTotal > 0 ? $vital['totalHigh'] / $checkupsTotal * 100 : 0;
                        $shareText = $vital['totalHigh'] > 0 && $share < 1 ? 'under 1%' : round($share).'%';
                    @endphp
                    <strong><span data-kpi-high="{{ $vital['totalHigh'] }}">{{ number_format($vital['totalHigh']) }}</span><small>high {{ Str::plural('reading', $vital['totalHigh']) }}</small></strong>
                    <span class="analytics-kpi-share">
                        @if ($checkupsTotal === 0)
                            No check-ups yet
                        @elseif ($vital['totalHigh'] === 0)
                            None of the {{ number_format($checkupsTotal) }} check-ups had {{ $vital['high_phrase'] }}
                        @else
                            {{ $shareText }} of check-ups had {{ $vital['high_phrase'] }}
                        @endif
                    </span>
                    <span class="analytics-kpi-foot">
                        <span><span data-kpi-low="{{ $vital['totalLow'] }}">{{ number_format($vital['totalLow']) }}</span> low {{ Str::plural('reading', $vital['totalLow']) }}</span>
                        <span>Normal: {{ $vital['normal'] }}</span>
                    </span>
                </button>
            @endforeach
        </div>

        <section class="analytics-chart-card analytics-chart-pie" aria-labelledby="analytics-pie-title">
            <header class="analytics-chart-header">
                <div>
                    <span class="analytics-eyebrow">Vital signs share</span>
                    <h2 id="analytics-pie-title">Abnormal readings by vital sign</h2>
                </div>
                <span class="analytics-chart-total"><span data-pie-total>0</span> abnormal readings</span>
            </header>
            <div class="analytics-pie-content">
                <div class="analytics-pie-legend" data-pie-legend></div>
                <div class="analytics-pie-chart" data-pie-chart role="img" aria-label="Abnormal readings by vital sign"></div>
            </div>
            <p class="analytics-chart-note">Which vital sign had the most high or low readings in {{ $periodLabel }}.</p>
        </section>
    </div>

    <div class="analytics-visuals analytics-vitals-layout">
        <section class="analytics-chart-card analytics-chart-primary" aria-labelledby="analytics-selected-title">
            <header class="analytics-chart-header">
                <div>
                    <span class="analytics-eyebrow">{{ $byDay ? 'Daily' : 'Monthly' }} activity</span>
                    <h2 id="analytics-selected-title">Patient check-ups</h2>
                </div>
                <span class="analytics-chart-total">{{ number_format($checkupsTotal) }} {{ Str::plural('check-up', $checkupsTotal) }}</span>
            </header>
            <div class="analytics-chart-canvas" data-bar-chart role="img" aria-label="Monthly patient check-ups bar chart"></div>
            <p class="analytics-chart-note">Number of check-ups recorded {{ $byDay ? 'each day in' : 'in each month of' }} {{ $periodLabel }}.</p>
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
            <div class="analytics-chart-canvas" data-line-chart role="img" aria-label="Monthly high and low vital sign screening chart"></div>
            <p class="analytics-chart-note">High and low readings each {{ $byDay ? 'day' : 'month' }} for the vital sign selected above. Click another card to switch.</p>
        </section>

    </div>
</section>

@push('scripts')
@endpush
