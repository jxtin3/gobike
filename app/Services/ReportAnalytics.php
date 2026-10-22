<?php

namespace App\Services;

use App\Models\Patient;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportAnalytics
{
    /**
     * The vital signs shown on the dashboard. The thresholds match the SQL in forRequest();
     * the plain-language texts are what an admin reads on the cards.
     */
    private const VITAL_SIGNS = [
        'blood_pressure' => [
            'code' => 'bp',
            'label' => 'Blood pressure',
            'unit' => 'mmHg',
            'normal' => '90/60 to 139/89',
            'high_short' => '140/90 or above',
            'low_short' => 'below 90/60',
            'high_range' => 'High: systolic ≥ 140 or diastolic ≥ 90 mmHg',
            'low_range' => 'Low: systolic < 90 or diastolic < 60 mmHg',
        ],
        'pulse' => [
            'code' => 'pulse',
            'label' => 'Pulse',
            'unit' => 'bpm (beats per minute)',
            'normal' => '60 to 100',
            'high_short' => 'above 100',
            'low_short' => 'below 60',
            'high_range' => 'High: > 100 bpm',
            'low_range' => 'Low: < 60 bpm',
        ],
        'respiration' => [
            'code' => 'resp',
            'label' => 'Respiration',
            'unit' => 'breaths per minute',
            'normal' => '12 to 20',
            'high_short' => 'above 20',
            'low_short' => 'below 12',
            'high_range' => 'High: > 20 per minute',
            'low_range' => 'Low: < 12 per minute',
        ],
        'temperature' => [
            'code' => 'temp',
            'label' => 'Temperature',
            'unit' => '°C',
            'normal' => '36.0 to 37.4',
            'high_short' => '37.5 or above',
            'low_short' => 'below 36.0',
            'high_range' => 'High: ≥ 37.5 °C',
            'low_range' => 'Low: < 36 °C',
        ],
        'blood_sugar' => [
            'code' => 'sugar',
            'label' => 'Blood sugar',
            'unit' => 'mg/dL',
            'normal' => '70 to 139',
            'high_short' => '140 or above',
            'low_short' => 'below 70',
            'high_range' => 'High: ≥ 140 mg/dL',
            'low_range' => 'Low: < 70 mg/dL',
            // Optional on a check-up, so percentages are of the patients who were tested.
            'optional' => true,
        ],
    ];

    public function forRequest(Request $request): array
    {
        $currentYear = (int) now()->format('Y');
        $earliest = Patient::min('recorded_at');
        $firstYear = $earliest ? min($currentYear, (int) substr((string) $earliest, 0, 4)) : $currentYear;

        $filters = $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:'.$currentYear],
            'month' => ['nullable', 'integer', 'between:1,12'],
        ]);

        $year = (int) ($filters['year'] ?? $currentYear);
        $month = isset($filters['month']) ? (int) $filters['month'] : null;

        // A whole year is charted month by month; one chosen month is charted day by day.
        $byDay = $month !== null;
        $from = CarbonImmutable::create($year, $month ?? 1, 1);
        $to = $byDay ? $from->endOfMonth()->startOfDay() : $from->endOfYear()->startOfMonth();
        $periodLabel = $byDay ? $from->format('F Y') : (string) $year;
        $keyFormat = $byDay ? 'Y-m-d' : 'Y-m';

        $monthExpression = match (DB::connection()->getDriverName()) {
            'sqlite' => $byDay ? "strftime('%Y-%m-%d', recorded_at)" : "strftime('%Y-%m', recorded_at)",
            'pgsql' => $byDay ? "TO_CHAR(recorded_at, 'YYYY-MM-DD')" : "TO_CHAR(recorded_at, 'YYYY-MM')",
            default => $byDay ? "DATE_FORMAT(recorded_at, '%Y-%m-%d')" : "DATE_FORMAT(recorded_at, '%Y-%m')",
        };

        $monthlyRows = Patient::query()
            ->whereBetween('recorded_at', [$from, $byDay ? $to->endOfDay() : $to->endOfMonth()])
            ->selectRaw("{$monthExpression} as report_month, COUNT(*) as checkups")
            ->tap(function ($query) {
                foreach (Patient::READINGS as $reading => $definition) {
                    $query->selectRaw("SUM(CASE WHEN {$definition['sql']} THEN 1 ELSE 0 END) as {$reading}");
                }
            })
            ->groupBy('report_month')
            ->get()
            ->keyBy('report_month');

        // People, not check-ups: a patient checked several times in the period counts once.
        $inPeriod = fn () => Patient::query()->whereBetween('recorded_at', [$from, $byDay ? $to->endOfDay() : $to->endOfMonth()]);
        $peopleTotal = $inPeriod()->onePerPerson()->count();

        $checkups = [];
        $vitals = [];
        foreach (self::VITAL_SIGNS as $key => $definition) {
            $vitals[$key] = [
                ...$definition,
                'high_phrase' => Patient::READINGS["{$definition['code']}_high"]['phrase'],
                'low_phrase' => Patient::READINGS["{$definition['code']}_low"]['phrase'],
                // Patient list filtered to this reading in the chosen period (the page shows names).
                'high_url' => route('admin.operations.patients.index', array_filter(['reading' => "{$definition['code']}_high", 'year' => $year, 'month' => $month])),
                'low_url' => route('admin.operations.patients.index', array_filter(['reading' => "{$definition['code']}_low", 'year' => $year, 'month' => $month])),
                // Patients this vital sign applies to: everyone, or only those who were tested.
                'peopleBase' => ! empty($definition['optional'])
                    ? $inPeriod()->whereNotNull('blood_sugar')->onePerPerson()->count()
                    : $peopleTotal,
                'peopleHigh' => $inPeriod()->withReading("{$definition['code']}_high")->onePerPerson()->count(),
                'peopleLow' => $inPeriod()->withReading("{$definition['code']}_low")->onePerPerson()->count(),
                'totalHigh' => 0,
                'totalLow' => 0,
                'months' => [],
            ];
        }

        for ($period = $from; $period->lessThanOrEqualTo($to); $period = $byDay ? $period->addDay() : $period->addMonth()) {
            $row = $monthlyRows->get($period->format($keyFormat));
            $label = $byDay ? $period->format('M j, Y') : $period->format('M Y');
            $shortLabel = $byDay ? $period->format('j') : $period->format('M');
            $checkups[] = [
                'label' => $label,
                'shortLabel' => $shortLabel,
                'count' => (int) ($row->checkups ?? 0),
                // Clicking a month bar opens that month; days have nothing further to open.
                'url' => $byDay ? null : route('admin.dashboard', ['year' => $year, 'month' => $period->month]),
            ];

            foreach (self::VITAL_SIGNS as $key => $definition) {
                $high = (int) ($row->{$definition['code'].'_high'} ?? 0);
                $low = (int) ($row->{$definition['code'].'_low'} ?? 0);
                $vitals[$key]['totalHigh'] += $high;
                $vitals[$key]['totalLow'] += $low;
                $vitals[$key]['months'][] = [
                    'label' => $label,
                    'shortLabel' => $shortLabel,
                    'high' => $high,
                    'low' => $low,
                ];
            }
        }

        $pie = array_map(static fn (string $key, array $vital) => [
            'key' => $key,
            'label' => $vital['label'],
            'high' => $vital['totalHigh'],
            'low' => $vital['totalLow'],
            'count' => $vital['totalHigh'] + $vital['totalLow'],
        ], array_keys($vitals), array_values($vitals));

        return [
            'checkups' => $checkups,
            'checkupsTotal' => array_sum(array_column($checkups, 'count')),
            'peopleTotal' => $peopleTotal,
            'vitals' => $vitals,
            'pie' => $pie,
            'year' => $year,
            'month' => $month,
            'periodLabel' => $periodLabel,
            'byDay' => $byDay,
            // Always offer the last five years, even before there are records for them, and any older year with data.
            'years' => range($currentYear, min($firstYear, $year, $currentYear - 4)),
        ];
    }
}
