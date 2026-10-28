<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\Ronda;
use App\Models\User;
use App\Support\ReportPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * How much field work GoBikers did in a period: rondas taken, time on ronda,
 * check-ups recorded, people checked (each person once) and check-ups with an abnormal reading.
 */
class GobikerActivity
{
    /**
     * One row per GoBiker, busiest first. GoBikers with no activity are included so the admin sees them.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function summary(ReportPeriod $period, ?string $search = null, ?string $barangay = null): Collection
    {
        $gobikers = User::where('role', 'GoBiker')
            ->when($search, fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
            ->when($barangay, fn ($q, $name) => $q->where('barangay', $name))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'barangay', 'mobile', 'status']);

        $ids = $gobikers->pluck('id');
        $rondas = Ronda::whereIn('user_id', $ids)->whereBetween('started_at', [$period->from, $period->to])->get()->groupBy('user_id');
        $checkups = $this->checkupCounts($period->from, $period->to, $ids);
        $people = $this->peopleCounts($period->from, $period->to, $ids);

        return $gobikers
            ->map(fn (User $gobiker) => [
                'user' => $gobiker,
                ...$this->rondaFigures($rondas->get($gobiker->id, collect())),
                'checkups' => (int) ($checkups[$gobiker->id]->checkups ?? 0),
                'abnormal' => (int) ($checkups[$gobiker->id]->abnormal ?? 0),
                'people' => (int) ($people[$gobiker->id] ?? 0),
            ])
            ->sortBy([['checkups', 'desc'], ['rondas', 'desc'], [fn ($a, $b) => strcmp($a['user']->name, $b['user']->name)]])
            ->values();
    }

    /**
     * One GoBiker's report: totals, a day-by-day (week) or week-by-week (month) breakdown,
     * and the rondas and check-ups themselves.
     */
    public function forGobiker(User $gobiker, ReportPeriod $period): array
    {
        $rondas = $gobiker->rondas()->whereBetween('started_at', [$period->from, $period->to])->orderBy('started_at')->get();
        $checkups = Patient::where('user_id', $gobiker->id)
            ->whereBetween('recorded_at', [$period->from, $period->to])
            ->orderBy('recorded_at')
            ->get();

        $breakdown = collect($period->buckets())->map(function (array $bucket) use ($rondas, $checkups) {
            $inBucket = fn ($date) => $date->betweenIncluded($bucket['from'], $bucket['to']);

            return [
                'label' => $bucket['label'],
                'from' => $bucket['from'],
                'rondas' => $rondas->filter(fn (Ronda $ronda) => $inBucket($ronda->started_at))->count(),
                'checkups' => $checkups->filter(fn (Patient $checkup) => $inBucket($checkup->recorded_at))->count(),
            ];
        });

        return [
            ...$this->rondaFigures($rondas),
            'checkups' => $checkups->count(),
            'abnormal' => $checkups->reject(fn (Patient $checkup) => $checkup->isNormal())->count(),
            'people' => $checkups->unique(fn (Patient $checkup) => $checkup->personKey())->count(),
            'breakdown' => $breakdown,
            'rondaList' => $rondas->sortByDesc('started_at')->values(),
            'checkupList' => $checkups->sortByDesc('recorded_at')->values(),
        ];
    }

    /** "3 h 20 min", "45 min" or "—". */
    public static function formatMinutes(int $minutes): string
    {
        if ($minutes <= 0) {
            return '—';
        }

        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        return trim(($hours ? "{$hours} h " : '').($rest || ! $hours ? "{$rest} min" : ''));
    }

    private function rondaFigures(Collection $rondas): array
    {
        return [
            'rondas' => $rondas->count(),
            'minutes' => (int) $rondas->sum(fn (Ronda $ronda) => $ronda->durationMinutes() ?? 0),
            'openRondas' => $rondas->whereNull('ended_at')->count(),
            'lastRonda' => $rondas->max('started_at'),
        ];
    }

    private function checkupCounts(CarbonImmutable $from, CarbonImmutable $to, Collection $ids): Collection
    {
        $abnormal = collect(Patient::READINGS)->pluck('sql')->map(fn ($sql) => "({$sql})")->implode(' OR ');

        return Patient::whereIn('user_id', $ids)
            ->whereBetween('recorded_at', [$from, $to])
            ->selectRaw("user_id, COUNT(*) as checkups, SUM(CASE WHEN {$abnormal} THEN 1 ELSE 0 END) as abnormal")
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');
    }

    /** People checked per GoBiker, each person counted once (same name and contact). */
    private function peopleCounts(CarbonImmutable $from, CarbonImmutable $to, Collection $ids): Collection
    {
        $perPerson = Patient::query()
            ->whereIn('user_id', $ids)
            ->whereBetween('recorded_at', [$from, $to])
            ->select('user_id')
            ->groupBy('user_id')
            ->groupByRaw(Patient::PERSON_SQL);

        return DB::query()->fromSub($perPerson, 'people')
            ->selectRaw('user_id, COUNT(*) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');
    }
}
