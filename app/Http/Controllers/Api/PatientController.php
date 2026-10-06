<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    /** A GoBiker only ever sees the records they recorded themselves. */
    public function index(Request $request): JsonResponse
    {
        $patients = Patient::where('user_id', $request->user()->id)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->limit(500)
            ->get();

        return response()->json([
            'patients' => $patients->map(fn (Patient $p) => $this->payload($p))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $patient = Patient::create($this->validated($request) + [
            'user_id' => $request->user()->id,
            'recorded_at' => now(),
        ]);

        return response()->json([
            'message' => 'Patient record saved.',
            'patient' => $this->payload($patient),
        ], 201);
    }

    public function update(Request $request, Patient $patient): JsonResponse
    {
        $this->authorizeOwner($request, $patient);
        $patient->update($this->validated($request));

        return response()->json([
            'message' => 'Patient record updated.',
            'patient' => $this->payload($patient->fresh()),
        ]);
    }

    public function destroy(Request $request, Patient $patient): JsonResponse
    {
        $this->authorizeOwner($request, $patient);
        $patient->delete();

        return response()->json(['message' => 'Patient record deleted.']);
    }

    // ---------------------------------------------------------------- helpers

    private function authorizeOwner(Request $request, Patient $patient): void
    {
        abort_unless(
            $patient->user_id === $request->user()->id,
            403,
            'You can only change your own patient records.'
        );
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'contact' => ['required', 'digits_between:7,11'],
            'age' => ['required', 'integer', 'between:0,120'],
            'sys' => ['required', 'integer', 'between:50,300'],
            'dia' => ['required', 'integer', 'between:30,200'],
            'pulse' => ['required', 'integer', 'between:20,250'],
            'resp' => ['required', 'integer', 'between:5,60'],
            'temp' => ['required', 'numeric', 'between:30,45'],
            'height' => ['required', 'numeric', 'between:30,250'],
            'weight' => ['required', 'numeric', 'between:1,300'],
            'barangay' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function payload(Patient $p): array
    {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'address' => $p->address,
            'contact' => $p->contact,
            'age' => $p->age,
            'sys' => $p->sys,
            'dia' => $p->dia,
            'pulse' => $p->pulse,
            'resp' => $p->resp,
            'temp' => $p->temp,
            'height' => $p->height,
            'weight' => $p->weight,
            'barangay' => $p->barangay,
            'recorded_at' => $p->recorded_at?->toIso8601String(),
        ];
    }
}