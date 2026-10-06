<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $barangays = Patient::query()
            ->whereNotNull('barangay')
            ->distinct()
            ->orderBy('barangay')
            ->pluck('barangay');

        $patients = Patient::with('recorder')
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($request->barangay, fn ($q, $b) => $q->where('barangay', $b))
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.operations.patients.index', [
            'patients' => $patients,
            'barangays' => $barangays,
            'total' => Patient::count(),
        ]);
    }

    public function show(Patient $patient)
    {
        $patient->load('recorder');

        return view('admin.operations.patients.show', compact('patient'));
    }
}