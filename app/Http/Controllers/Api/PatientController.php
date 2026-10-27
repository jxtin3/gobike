<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PatientFollowUp;
use App\Services\Push\PushNotifier;
use App\Support\Barangays;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

        // One query for the admin decisions of everyone in the list.
        $followUps = PatientFollowUp::whereIn('person_key', $patients->map->personKey()->unique()->values())
            ->get()
            ->keyBy('person_key');

        return response()->json([
            'patients' => $patients->map(fn (Patient $p) => $this->payload($p, $followUps->get($p->personKey())))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $patient = Patient::create($this->validated($request) + [
            'user_id' => $request->user()->id,
            'recorded_at' => now(),
        ]);
        app(PushNotifier::class)->checkupSaved($patient, updated: false);

        return response()->json([
            'message' => 'Patient record saved.',
            'patient' => $this->payload($patient),
        ], 201);
    }

    public function update(Request $request, Patient $patient): JsonResponse
    {
        $this->authorizeOwner($request, $patient);
        $patient->update($this->validated($request));
        app(PushNotifier::class)->checkupSaved($patient, updated: true);

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
            // Optional: not every patient is tested. mg/dL.
            'blood_sugar' => ['nullable', 'integer', 'between:20,600'],
            'barangay' => ['nullable', 'string', Rule::in(Barangays::ALL)],
            // Advice for the resident, who can read it in the app. Older app versions don't send it.
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function payload(Patient $p, PatientFollowUp|false|null $followUp = false): array
    {
        // false = not preloaded, look it up
        $followUp = $followUp === false ? $p->followUp() : $followUp;

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
            'blood_sugar' => $p->blood_sugar,
            'remarks' => $p->remarks,
            'barangay' => $p->barangay,
            'recorded_at' => $p->recorded_at?->toIso8601String(),
            // The admin's decision on whether to keep checking this person (for the app to show).
            'follow_up' => $followUp ? [
                'decision' => $followUp->decision,
                'note' => $followUp->note,
                'decided_at' => $followUp->decided_at->toIso8601String(),
            ] : null,
        ];
    }
}
