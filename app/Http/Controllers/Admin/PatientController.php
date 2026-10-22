<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Patient;
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

        return view('admin.operations.patients.index', [
            'patients' => $patients,
            'barangays' => $barangays,
            'reading' => $filters['reading'] ?? null,
            'period' => $period,
        ]);
    }

    public function show(Patient $patient)
    {
        $patient->load('recorder');

        return view('admin.operations.patients.show', compact('patient'));
    }

    public function destroy(Patient $patient)
    {
        $patient->delete();

        return redirect()->route('admin.operations.patients.index')
            ->with('success', 'Patient record deleted.');
    }
}
