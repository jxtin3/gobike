<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ronda;
use App\Models\User;
use App\Services\GobikerActivity;
use App\Support\CsvDownload;
use App\Support\ReportPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/** Weekly or monthly reports of how many rondas each GoBiker took and how many patients they checked. */
class GobikerReportController extends Controller
{
    public function index(Request $request, GobikerActivity $activity)
    {
        $period = ReportPeriod::fromRequest($request);
        $search = trim((string) $request->query('search', ''));
        $rows = $activity->summary($period, $search ?: null);

        $totals = [
            'rondas' => $rows->sum('rondas'),
            'minutes' => $rows->sum('minutes'),
            'checkups' => $rows->sum('checkups'),
            'people' => $rows->sum('people'),
            'abnormal' => $rows->sum('abnormal'),
            'active' => $rows->filter(fn ($row) => $row['rondas'] > 0 || $row['checkups'] > 0)->count(),
        ];

        if ($request->query('format') === 'csv') {
            return CsvDownload::make(
                'gobiker-report-'.$period->type.'-'.$period->from->toDateString().'.csv',
                ['GoBiker', 'Email', 'Barangay', 'Rondas', 'Time on ronda', 'Check-ups', 'People checked', 'Check-ups with abnormal readings', 'Last ronda'],
                $rows->map(fn ($row) => [
                    $row['user']->name,
                    $row['user']->email,
                    $row['user']->barangay,
                    $row['rondas'],
                    GobikerActivity::formatMinutes($row['minutes']),
                    $row['checkups'],
                    $row['people'],
                    $row['abnormal'],
                    $row['lastRonda']?->format('Y-m-d H:i'),
                ])
            );
        }

        return view('admin.operations.gobiker-reports.index', [
            ...compact('period', 'rows', 'totals', 'search'),
            'trackingSince' => $this->trackingSince(),
        ]);
    }

    public function show(Request $request, User $gobiker, GobikerActivity $activity)
    {
        abort_unless($gobiker->role === 'GoBiker', 404);

        $period = ReportPeriod::fromRequest($request);
        $report = $activity->forGobiker($gobiker, $period);

        if ($request->query('format') === 'csv') {
            $rows = $report['checkupList']->map(fn ($checkup) => [
                'Check-up',
                $checkup->recorded_at->format('Y-m-d H:i'),
                $checkup->name,
                $checkup->barangay,
                "{$checkup->sys}/{$checkup->dia}",
                $checkup->pulse,
                $checkup->resp,
                $checkup->temp,
                $checkup->blood_sugar,
                implode(', ', $checkup->abnormalReadings()) ?: 'Normal',
            ])->concat($report['rondaList']->map(fn ($ronda) => [
                'Ronda',
                $ronda->started_at->format('Y-m-d H:i'),
                $ronda->ended_at ? 'Ended '.$ronda->ended_at->format('Y-m-d H:i') : 'Not stopped',
                $ronda->barangay,
                GobikerActivity::formatMinutes($ronda->durationMinutes() ?? 0),
                '', '', '', '', '',
            ]));

            return CsvDownload::make(
                str($gobiker->name)->slug().'-'.$period->type.'-'.$period->from->toDateString().'.csv',
                ['Type', 'Date', 'Patient / end', 'Barangay', 'BP / duration', 'Pulse', 'Respiration', 'Temperature', 'Blood sugar (mg/dL)', 'Readings'],
                $rows
            );
        }

        return view('admin.operations.gobiker-reports.show', [
            ...compact('gobiker', 'period', 'report'),
            'trackingSince' => $this->trackingSince(),
        ]);
    }

    /** When ronda logging started (the first ronda ever recorded), or null if none yet. */
    private function trackingSince(): ?CarbonImmutable
    {
        $first = Ronda::min('started_at');

        return $first ? CarbonImmutable::parse($first) : null;
    }
}
