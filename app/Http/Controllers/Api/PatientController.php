<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PatientFollowUp;
use App\Services\Push\PushNotifier;
use App\Support\Barangays;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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
        $data = $this->validated($request, Patient::consentRules());

        $patient = Patient::create(Arr::except($data, ['consent']) + [
            'consent_given' => Patient::consentFrom($data),
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
        $oldKey = $patient->personKey();
        $patient->update($this->validated($request));
        $this->moveFollowUp($oldKey, $patient);
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

    /**
     * The name or contact of a check-up was corrected. When no other check-up still uses the old
     * name and contact, the admin's follow-up decision moves with it instead of being left behind.
     */
    private function moveFollowUp(string $oldKey, Patient $patient): void
    {
        $newKey = $patient->personKey();

        if ($oldKey === $newKey || PatientFollowUp::where('person_key', $newKey)->exists()) {
            return;
        }

        [$oldName, $oldContact] = explode('|', $oldKey, 2);

        if (Patient::forPerson($oldName, $oldContact)->exists()) {
            return;
        }

        PatientFollowUp::where('person_key', $oldKey)->update([
            'person_key' => $newKey,
            'name' => $patient->name,
            'contact' => $patient->contact,
        ]);
    }

    private function authorizeOwner(Request $request, Patient $patient): void
    {
        abort_unless(
            $patient->user_id === $request->user()->id,
            403,
            'You can only change your own patient records.'
        );
    }

    /** Consent is asked once, when the check-up is recorded; corrections later keep it as it was. */
    private function validated(Request $request, array $extraRules = []): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'contact' => ['required', 'digits_between:7,11'],
            'age' => ['required', 'integer', 'between:0,120'],
            'barangay' => ['nullable', 'string', Rule::in(Barangays::ALL)],
        ] + Patient::vitalRules() + $extraRules);
    }

    public function payload(Patient $p, PatientFollowUp|false|null $followUp = false): array
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
            // true, or null for check-ups recorded by app versions that did not ask.
            'consent_given' => $p->consent_given,
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
