<?php

namespace App\Services;

use App\Models\Patient;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportAnalytics
{
    /**
     * The four vital signs shown on the dashboard. The thresholds match the SQL in forRequest();
     * the plain-language texts are what an admin reads on the cards.
     */
    private const VITAL_SIGNS = [
        'blood_pressure' => [
            'label' => 'Blood pressure',
            'unit' => 'mmHg',
            'normal' => '90/60 to 139/89',
            'high_short' => '140/90 or above',
            'low_short' => 'below 90/60',
            'high_phrase' => 'high blood pressure',
            'low_phrase' => 'low blood pressure',
            'high_range' => 'High: systolic ≥ 140 or diastolic ≥ 90 mmHg',
            'low_range' => 'Low: systolic < 90 or diastolic < 60 mmHg',
        ],
        'pulse' => [
            'label' => 'Pulse',
            'unit' => 'bpm (beats per minute)',
            'normal' => '60 to 100',
            'high_short' => 'above 100',
            'low_short' => 'below 60',
            'high_phrase' => 'a fast pulse',
            'low_phrase' => 'a slow pulse',
            'high_range' => 'High: > 100 bpm',
            'low_range' => 'Low: < 60 bpm',
        ],
        'respiration' => [
            'label' => 'Respiration',
            'unit' => 'breaths per minute',
            'normal' => '12 to 20',
            'high_short' => 'above 20',
            'low_short' => 'below 12',
            'high_phrase' => 'fast breathing',
            'low_phrase' => 'slow breathing',
            'high_range' => 'High: > 20 per minute',
            'low_range' => 'Low: < 12 per minute',
        ],
        'temperature' => [
            'label' => 'Temperature',
            'unit' => '°C',
            'normal' => '36.0 to 37.4',
            'high_short' => '37.5 or above',
            'low_short' => 'below 36.0',
            'high_phrase' => 'a high temperature',
            'low_phrase' => 'a low temperature',
            'high_range' => 'High: ≥ 37.5 °C',
            'low_range' => 'Low: < 36 °C',
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
            ->selectRaw('SUM(CASE WHEN sys >= 140 OR dia >= 90 THEN 1 ELSE 0 END) as bp_high')
            ->selectRaw('SUM(CASE WHEN sys < 90 OR dia < 60 THEN 1 ELSE 0 END) as bp_low')
            ->selectRaw('SUM(CASE WHEN pulse > 100 THEN 1 ELSE 0 END) as pulse_high')
            ->selectRaw('SUM(CASE WHEN pulse < 60 THEN 1 ELSE 0 END) as pulse_low')
            ->selectRaw('SUM(CASE WHEN resp > 20 THEN 1 ELSE 0 END) as resp_high')
            ->selectRaw('SUM(CASE WHEN resp < 12 THEN 1 ELSE 0 END) as resp_low')
            ->selectRaw('SUM(CASE WHEN temp >= 37.5 THEN 1 ELSE 0 END) as temp_high')
            ->selectRaw('SUM(CASE WHEN temp < 36 THEN 1 ELSE 0 END) as temp_low')
            ->groupBy('report_month')
            ->get()
            ->keyBy('report_month');

        $checkups = [];
        $vitals = [];
        foreach (self::VITAL_SIGNS as $key => $definition) {
            $vitals[$key] = [
                ...$definition,
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
            ];

            foreach (self::VITAL_SIGNS as $key => $definition) {
                [$highColumn, $lowColumn] = match ($key) {
                    'blood_pressure' => ['bp_high', 'bp_low'],
                    'pulse' => ['pulse_high', 'pulse_low'],
                    'respiration' => ['resp_high', 'resp_low'],
                    'temperature' => ['temp_high', 'temp_low'],
                };
                $high = (int) ($row->{$highColumn} ?? 0);
                $low = (int) ($row->{$lowColumn} ?? 0);
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
            'vitals' => $vitals,
            'pie' => $pie,
            'year' => $year,
            'month' => $month,
            'periodLabel' => $periodLabel,
            'byDay' => $byDay,
            'years' => range($currentYear, min($firstYear, $year)),
        ];
    }
}
