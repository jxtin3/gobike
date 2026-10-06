<?php

namespace App\Services;

use App\Models\ContactMessage;
use App\Models\Donation;
use App\Models\GobikerMessage;
use App\Models\News;
use App\Models\Patient;
use App\Models\Picture;
use App\Models\User;
use App\Models\VolunteerApplication;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportAnalytics
{
    public function forRequest(Request $request): array
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $selectedMonth = $filters['month'] ?? now()->format('Y-m');
        $year = (int) substr($selectedMonth, 0, 4);
        $from = CarbonImmutable::create($year, 1, 1)->startOfMonth();
        $to = $from->addYear()->subMonth();

        $months = [];
        for ($month = $from; $month->lessThanOrEqualTo($to); $month = $month->addMonth()) {
            $months[$month->format('Y-m')] = [
                'label' => $month->format('M Y'),
                'shortLabel' => $month->format('M'),
                'count' => 0,
            ];
        }

        $datasets = [
            'patients' => ['Patient check-ups', Patient::query(), 'recorded_at'],
            'gobikers' => ['GoBiker registrations', User::query()->where('role', 'GoBiker'), 'created_at'],
            'donations' => ['Paid donations', Donation::query()->where('status', 'paid'), 'updated_at'],
            'volunteers' => ['Volunteer applications', VolunteerApplication::query(), 'created_at'],
            'contact_messages' => ['Contact messages', ContactMessage::query(), 'created_at'],
            'gobiker_messages' => ['GoBiker messages', GobikerMessage::query(), 'created_at'],
            'news' => ['Published news', News::query()->where('is_published', true)->whereNotNull('published_at'), 'published_at'],
            'pictures' => ['Gallery pictures', Picture::query(), 'created_at'],
        ];

        $metrics = [];
        foreach ($datasets as $key => [$label, $query, $dateColumn]) {
            $counts = $this->monthlyCounts($query, $dateColumn, $from, $to);
            $series = $months;

            foreach ($counts as $month => $count) {
                if (isset($series[$month])) {
                    $series[$month]['count'] = (int) $count;
                }
            }

            $metrics[] = [
                'key' => $key,
                'label' => $label,
                'total' => array_sum(array_column($series, 'count')),
                'months' => array_values($series),
            ];
        }

        return [
            'metrics' => $metrics,
            'year' => $year,
            'selectedMonth' => $selectedMonth,
        ];
    }

    private function monthlyCounts(
        Builder $query,
        string $dateColumn,
        CarbonImmutable $from,
        CarbonImmutable $to
    ): array {
        $monthExpression = match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', {$dateColumn})",
            'pgsql' => "TO_CHAR({$dateColumn}, 'YYYY-MM')",
            default => "DATE_FORMAT({$dateColumn}, '%Y-%m')",
        };

        return $query
            ->whereBetween($dateColumn, [$from->startOfMonth(), $to->endOfMonth()])
            ->selectRaw("{$monthExpression} as report_month, COUNT(*) as total")
            ->groupBy('report_month')
            ->pluck('total', 'report_month')
            ->all();
    }
}
