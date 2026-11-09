<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Services\Push\PushNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Visit requests from residents, as a GoBiker sees them. */
class GobikerAppointmentController extends Controller
{
    /**
     * Requests residents sent to this GoBiker (to accept or decline), older requests that any
     * GoBiker in the barangay may take, and the visits they accepted.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $gobiker */
        $gobiker = $request->user();

        $requests = Appointment::with('resident:id,name,mobile,birthdate')
            ->where('status', 'pending')
            ->where('gobiker_id', $gobiker->id)
            ->orderBy('preferred_date')
            ->oldest()
            ->get();

        // Sent from app versions up to 1.0.10, which did not let the resident choose.
        $available = Appointment::with('resident:id,name,mobile,birthdate')
            ->where('status', 'pending')
            ->whereNull('gobiker_id')
            ->where('barangay', $gobiker->barangay)
            ->orderBy('preferred_date')
            ->oldest()
            ->get();

        $mine = Appointment::with('resident:id,name,mobile,birthdate', 'gobiker:id,name')
            ->where('gobiker_id', $gobiker->id)
            ->where(fn ($q) => $q->where('status', 'accepted')
                ->orWhere(fn ($done) => $done->where('status', 'completed')->where('completed_at', '>=', now()->subDays(30))))
            ->orderByRaw("CASE status WHEN 'accepted' THEN 0 ELSE 1 END")
            ->orderBy('preferred_date')
            ->get();

        return response()->json([
            'requests' => $requests->map(fn (Appointment $a) => $a->toApi(true))->values(),
            'available' => $available->map(fn (Appointment $a) => $a->toApi(true))->values(),
            'mine' => $mine->map(fn (Appointment $a) => $a->toApi(true))->values(),
            // For the dashboard counter: requests to answer and visits still to do.
            'counts' => [
                'requests' => $requests->count(),
                'available' => $available->count(),
                'to_visit' => $mine->where('status', 'accepted')->count(),
            ],
        ]);
    }

    public function accept(Request $request, Appointment $appointment): JsonResponse
    {
        /** @var User $gobiker */
        $gobiker = $request->user();

        $sentToMe = $appointment->gobiker_id === $gobiker->id;
        $openInMyBarangay = $appointment->gobiker_id === null && $appointment->barangay === $gobiker->barangay;
        if (! $sentToMe && ! $openInMyBarangay) {
            // An open request someone else took first is a race, not a permission problem.
            abort_if($appointment->status === 'pending' || $appointment->barangay !== $gobiker->barangay, 403, 'This request was sent to another GoBiker.');

            return response()->json(['message' => 'Another GoBiker already accepted this request.'], 409);
        }

        // Only one GoBiker can take an open request, even when two tap "Accept" at the same moment.
        $taken = Appointment::whereKey($appointment->id)
            ->where('status', 'pending')
            ->where(fn ($q) => $q->where('gobiker_id', $gobiker->id)->orWhereNull('gobiker_id'))
            ->update(['status' => 'accepted', 'gobiker_id' => $gobiker->id, 'accepted_at' => now()]);

        if ($taken === 0) {
            return response()->json(['message' => 'This request was cancelled, or another GoBiker already accepted it.'], 409);
        }

        $appointment = $appointment->fresh(['resident', 'gobiker']);
        app(PushNotifier::class)->appointmentChanged($appointment);

        return response()->json([
            'message' => 'Visit accepted.',
            'appointment' => $appointment->toApi(true),
        ]);
    }

    /**
     * The GoBiker can't make a visit sent to them (or one they accepted and now can't do); the
     * resident can then ask someone else.
     */
    public function decline(Request $request, Appointment $appointment): JsonResponse
    {
        /** @var User $gobiker */
        $gobiker = $request->user();

        abort_unless($appointment->gobiker_id === $gobiker->id, 403, 'This request was not sent to you.');

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:300'],
        ]);

        $declined = Appointment::whereKey($appointment->id)
            ->whereIn('status', ['pending', 'accepted'])
            ->update([
                'status' => 'declined',
                'decline_note' => trim((string) ($data['note'] ?? '')) ?: "{$gobiker->name} can't make this visit. You can ask another GoBiker.",
            ]);

        if ($declined === 0) {
            return response()->json(['message' => 'Only a request waiting for your answer, or a visit still to do, can be declined.'], 422);
        }

        $appointment = $appointment->fresh(['resident', 'gobiker']);
        app(PushNotifier::class)->appointmentChanged($appointment);

        return response()->json([
            'message' => 'Request declined. The resident can ask another GoBiker.',
            'appointment' => $appointment->toApi(true),
        ]);
    }

    /**
     * Records the check-up at an accepted visit and finishes the visit in one step. The person's
     * details are the resident's own (their name and mobile, the visit's address and barangay,
     * their age from the birthdate), so the GoBiker only sends what they measured.
     */
    public function checkup(Request $request, Appointment $appointment): JsonResponse
    {
        /** @var User $gobiker */
        $gobiker = $request->user();

        abort_unless($appointment->gobiker_id === $gobiker->id, 403, 'This visit is not assigned to you.');

        if ($appointment->status !== 'accepted') {
            return response()->json(['message' => 'A check-up can only be recorded at an accepted visit.'], 422);
        }

        $resident = $appointment->resident;
        $knownAge = $resident?->age;

        $data = $request->validate([
            // Only asked for when the resident never gave a birthdate.
            'age' => [$knownAge === null ? 'required' : 'nullable', 'integer', 'between:0,120'],
        ] + Patient::vitalRules());

        $patient = DB::transaction(function () use ($appointment, $gobiker, $resident, $data, $knownAge) {
            // Taken first, so two taps at once cannot record two check-ups for one visit.
            $taken = Appointment::whereKey($appointment->id)
                ->where('status', 'accepted')
                ->update(['status' => 'completed', 'completed_at' => now()]);

            if ($taken === 0) {
                return null;
            }

            $patient = Patient::create([
                'user_id' => $gobiker->id,
                'resident_id' => $resident?->id, // set on purpose, not guessed from the name
                'barangay' => $appointment->barangay,
                'name' => $resident?->name ?? 'Resident',
                'address' => $appointment->address,
                'contact' => $resident?->mobile,
                'age' => $knownAge ?? $data['age'],
                'recorded_at' => now(),
            ] + Arr::except($data, ['age']));

            $appointment->update(['patient_id' => $patient->id]);

            return $patient;
        });

        if ($patient === null) {
            return response()->json(['message' => 'This visit was already finished.'], 422);
        }

        $appointment = $appointment->fresh(['resident', 'gobiker']);
        // One notification is enough: "Home visit done" already points to the Medical History.
        app(PushNotifier::class)->appointmentChanged($appointment);

        return response()->json([
            'message' => 'Check-up saved. Visit done.',
            'appointment' => $appointment->toApi(true),
            'patient' => app(PatientController::class)->payload($patient),
        ], 201);
    }

    /** Marks the visit done, optionally linking the check-up the GoBiker recorded there. */
    public function complete(Request $request, Appointment $appointment): JsonResponse
    {
        /** @var User $gobiker */
        $gobiker = $request->user();

        abort_unless($appointment->gobiker_id === $gobiker->id, 403, 'This visit is not assigned to you.');

        if ($appointment->status !== 'accepted') {
            return response()->json(['message' => 'Only an accepted visit can be marked done.'], 422);
        }

        $data = $request->validate([
            // Must be one of this GoBiker's own check-ups.
            'patient_id' => ['nullable', 'integer', Rule::exists(Patient::class, 'id')->where('user_id', $gobiker->id)],
        ]);

        $appointment->update([
            'status' => 'completed',
            'completed_at' => now(),
            'patient_id' => $data['patient_id'] ?? null,
        ]);

        $appointment = $appointment->fresh(['resident', 'gobiker']);
        app(PushNotifier::class)->appointmentChanged($appointment);

        return response()->json([
            'message' => 'Visit marked as done.',
            'appointment' => $appointment->toApi(true),
        ]);
    }
}
