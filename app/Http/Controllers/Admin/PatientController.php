<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PatientFollowUp;
use App\Support\CsvDownload;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'barangay' => ['nullable', 'string', 'max:255'],
            'reading' => ['nullable', Rule::in(array_keys(Patient::READINGS))],
            'year' => ['nullable', 'integer', 'min:2000', 'max:'.now()->year],
            'month' => ['nullable', 'integer', 'between:1,12'],
        ]);

        $period = null;
        if (isset($filters['year'])) {
            $from = CarbonImmutable::create((int) $filters['year'], (int) ($filters['month'] ?? 1), 1)->startOfDay();
            $to = isset($filters['month']) ? $from->endOfMonth() : $from->endOfYear();
            $period = ['from' => $from, 'to' => $to, 'label' => isset($filters['month']) ? $from->format('F Y') : (string) $filters['year']];
        }

        $barangays = Patient::query()
            ->whereNotNull('barangay')
            ->distinct()
            ->orderBy('barangay')
            ->pluck('barangay');

        $patients = Patient::with('recorder')
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($request->barangay, fn ($q, $b) => $q->where('barangay', $b))
            ->when($filters['reading'] ?? null, fn ($q, $reading) => $q->withReading($reading))
            ->when($period, fn ($q, $p) => $q->whereBetween('recorded_at', [$p['from'], $p['to']]))
            // From the dashboard each patient appears once, with their latest matching check-up.
            ->when($filters['reading'] ?? null, fn ($q) => $q->onePerPerson())
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        // Follow-up status of the people on this page: the admin's decision, or a hint from their latest check-ups.
        $keys = $patients->getCollection()->map->personKey()->unique()->values();
        $decisions = PatientFollowUp::whereIn('person_key', $keys)->pluck('decision', 'person_key');
        $followUps = $patients->getCollection()->mapWithKeys(function (Patient $patient) use ($decisions) {
            $key = $patient->personKey();
            if ($decisions->has($key)) {
                return [$patient->id => $decisions[$key]];
            }

            $latest = Patient::samePersonAs($patient)->orderByDesc('recorded_at')->orderByDesc('id')
                ->limit(Patient::NORMAL_CHECKUPS_TO_STOP)->get();

            return [$patient->id => Patient::followUpSuggestion($latest)['state'] === 'can_stop' ? 'can_stop' : null];
        });

        return view('admin.operations.patients.index', [
            'followUps' => $followUps,
            'patients' => $patients,
            'barangays' => $barangays,
            'reading' => $filters['reading'] ?? null,
            'period' => $period,
        ]);
    }

    public function show(Request $request, Patient $patient)
    {
        $patient->load('recorder');

        // Every check-up of this person (same name and contact), newest first.
        $history = Patient::samePersonAs($patient)
            ->with('recorder:id,name')
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->get();

        if ($request->query('format') === 'csv') {
            return CsvDownload::make(
                str($patient->name)->slug().'-check-ups.csv',
                ['Date', 'Name', 'Age', 'Address', 'Contact', 'Barangay', 'Blood pressure', 'Pulse', 'Respiration', 'Temperature', 'Height', 'Weight', 'Readings', 'Recorded by'],
                $history->map(fn (Patient $checkup) => [
                    $checkup->recorded_at->format('Y-m-d H:i'),
                    $checkup->name,
                    $checkup->age,
                    $checkup->address,
                    $checkup->contact,
                    $checkup->barangay,
                    "{$checkup->sys}/{$checkup->dia}",
                    $checkup->pulse,
                    $checkup->resp,
                    $checkup->temp,
                    $checkup->height,
                    $checkup->weight,
                    implode(', ', $checkup->abnormalReadings()) ?: 'Normal',
                    $checkup->recorder?->name,
                ])
            );
        }

        return view('admin.operations.patients.show', [
            'patient' => $patient,
            'history' => $history,
            'suggestion' => Patient::followUpSuggestion($history),
            'followUp' => PatientFollowUp::with('decider:id,name')->where('person_key', $patient->personKey())->first(),
        ]);
    }

    public function destroy(Patient $patient)
    {
        $patient->delete();

        return redirect()->route('admin.operations.patients.index')
            ->with('success', 'Patient record deleted.');
    }
}
