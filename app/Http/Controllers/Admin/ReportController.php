<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m'],
            'to' => ['nullable', 'date_format:Y-m'],
        ]);

        $fromMonth = $filters['from'] ?? now()->subMonths(11)->format('Y-m');
        $toMonth = $filters['to'] ?? now()->format('Y-m');
        $from = CarbonImmutable::createFromFormat('!Y-m', $fromMonth);
        $to = CarbonImmutable::createFromFormat('!Y-m', $toMonth);

        if ($from->greaterThan($to)) {
            throw ValidationException::withMessages([
                'from' => 'The start month must be before or equal to the end month.',
            ]);
        }

        if ($from->diffInMonths($to) > 35) {
            throw ValidationException::withMessages([
                'to' => 'Choose a date range of 36 months or less.',
            ]);
        }

        $months = [];
        for ($month = $from; $month->lessThanOrEqualTo($to); $month = $month->addMonth()) {
            $months[$month->format('Y-m')] = [
                'label' => $month->format('M Y'),
                'shortLabel' => $month->format('M'),
                'count' => 0,
            ];
        }

        $datasets = [
            'Patient check-ups' => [Patient::query(), 'recorded_at'],
            'GoBiker registrations' => [User::query()->where('role', 'GoBiker'), 'created_at'],
            'Paid donations' => [Donation::query()->where('status', 'paid'), 'updated_at'],
            'Volunteer applications' => [VolunteerApplication::query(), 'created_at'],
            'Contact messages' => [ContactMessage::query(), 'created_at'],
            'GoBiker messages' => [GobikerMessage::query(), 'created_at'],
            'Published news' => [News::query()->where('is_published', true)->whereNotNull('published_at'), 'published_at'],
            'Gallery pictures' => [Picture::query(), 'created_at'],
        ];

        $charts = [];
        foreach ($datasets as $label => [$query, $dateColumn]) {
            $counts = $this->monthlyCounts($query, $dateColumn, $from, $to);
            $chartMonths = $months;

            foreach ($counts as $month => $count) {
                if (isset($chartMonths[$month])) {
                    $chartMonths[$month]['count'] = (int) $count;
                }
            }

            $total = array_sum(array_column($chartMonths, 'count'));
            $max = max(array_column($chartMonths, 'count'));

            $charts[] = [
                'label' => $label,
                'total' => $total,
                'months' => array_map(fn (array $item) => [
                    ...$item,
                    'height' => $max === 0 ? 0 : max(4, (int) round($item['count'] / $max * 100)),
                ], $chartMonths),
            ];
        }

        return view('admin.operations.reports.index', [
            'charts' => $charts,
            'fromMonth' => $fromMonth,
            'toMonth' => $toMonth,
        ]);
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
