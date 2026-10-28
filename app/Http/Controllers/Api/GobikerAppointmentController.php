<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Services\Push\PushNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        $requests = Appointment::with('resident:id,name,mobile')
            ->where('status', 'pending')
            ->where('gobiker_id', $gobiker->id)
            ->orderBy('preferred_date')
            ->oldest()
            ->get();

        // Sent from app versions up to 1.0.10, which did not let the resident choose.
        $available = Appointment::with('resident:id,name,mobile')
            ->where('status', 'pending')
            ->whereNull('gobiker_id')
            ->where('barangay', $gobiker->barangay)
            ->orderBy('preferred_date')
            ->oldest()
            ->get();

        $mine = Appointment::with('resident:id,name,mobile', 'gobiker:id,name')
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

    /** The GoBiker can't make a visit sent to them; the resident can then ask someone else. */
    public function decline(Request $request, Appointment $appointment): JsonResponse
    {
        /** @var User $gobiker */
        $gobiker = $request->user();

        abort_unless($appointment->gobiker_id === $gobiker->id, 403, 'This request was not sent to you.');

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:300'],
        ]);

        $declined = Appointment::whereKey($appointment->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'declined',
                'decline_note' => trim((string) ($data['note'] ?? '')) ?: "{$gobiker->name} can't make this visit. You can ask another GoBiker.",
            ]);

        if ($declined === 0) {
            return response()->json(['message' => 'Only a request waiting for your answer can be declined.'], 422);
        }

        $appointment = $appointment->fresh(['resident', 'gobiker']);
        app(PushNotifier::class)->appointmentChanged($appointment);

        return response()->json([
            'message' => 'Request declined. The resident can ask another GoBiker.',
            'appointment' => $appointment->toApi(true),
        ]);
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
