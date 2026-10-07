<?php

namespace App\Services;

use App\Models\Patient;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportAnalytics
{
    private const VITAL_SIGNS = [
        'blood_pressure' => [
            'label' => 'Blood pressure',
            'high_range' => 'High: systolic ≥ 140 or diastolic ≥ 90 mmHg',
            'low_range' => 'Low: systolic < 90 or diastolic < 60 mmHg',
        ],
        'pulse' => [
            'label' => 'Pulse',
            'high_range' => 'High: > 100 bpm',
            'low_range' => 'Low: < 60 bpm',
        ],
        'respiration' => [
            'label' => 'Respiration',
            'high_range' => 'High: > 20 per minute',
            'low_range' => 'Low: < 12 per minute',
        ],
        'temperature' => [
            'label' => 'Temperature',
            'high_range' => 'High: ≥ 37.5 °C',
            'low_range' => 'Low: < 36 °C',
        ],
    ];

    public function forRequest(Request $request): array
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $selectedMonth = $filters['month'] ?? now()->format('Y-m');
        $year = (int) substr($selectedMonth, 0, 4);
        $from = CarbonImmutable::create($year, 1, 1)->startOfMonth();
        $to = $from->addYear()->subMonth();
        $monthExpression = match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', recorded_at)",
            'pgsql' => "TO_CHAR(recorded_at, 'YYYY-MM')",
            default => "DATE_FORMAT(recorded_at, '%Y-%m')",
        };

        $monthlyRows = Patient::query()
            ->whereBetween('recorded_at', [$from->startOfMonth(), $to->endOfMonth()])
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

        for ($month = $from; $month->lessThanOrEqualTo($to); $month = $month->addMonth()) {
            $monthKey = $month->format('Y-m');
            $row = $monthlyRows->get($monthKey);
            $label = $month->format('M Y');
            $shortLabel = $month->format('M');
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
            'selectedMonth' => $selectedMonth,
        ];
    }
}
