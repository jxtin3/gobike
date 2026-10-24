<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Visit requests from residents, as a GoBiker sees them. */
class GobikerAppointmentController extends Controller
{
    /** Unassigned requests in the GoBiker's barangay, plus the ones they accepted. */
    public function index(Request $request): JsonResponse
    {
        /** @var User $gobiker */
        $gobiker = $request->user();

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
            'available' => $available->map(fn (Appointment $a) => $a->toApi(true))->values(),
            'mine' => $mine->map(fn (Appointment $a) => $a->toApi(true))->values(),
            // For the dashboard counter: visits still to do.
            'counts' => [
                'available' => $available->count(),
                'to_visit' => $mine->where('status', 'accepted')->count(),
            ],
        ]);
    }

    public function accept(Request $request, Appointment $appointment): JsonResponse
    {
        /** @var User $gobiker */
        $gobiker = $request->user();

        abort_unless(
            $appointment->barangay === $gobiker->barangay,
            403,
            'This request is outside your barangay.'
        );

        // Only one GoBiker can take it, even when two tap "Accept" at the same moment.
        $taken = Appointment::whereKey($appointment->id)
            ->where('status', 'pending')
            ->whereNull('gobiker_id')
            ->update(['status' => 'accepted', 'gobiker_id' => $gobiker->id, 'accepted_at' => now()]);

        if ($taken === 0) {
            return response()->json(['message' => 'Another GoBiker already accepted this request, or it was cancelled.'], 409);
        }

        return response()->json([
            'message' => 'Visit accepted.',
            'appointment' => $appointment->fresh(['resident:id,name,mobile', 'gobiker:id,name'])->toApi(true),
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

        return response()->json([
            'message' => 'Visit marked as done.',
            'appointment' => $appointment->fresh(['resident:id,name,mobile', 'gobiker:id,name'])->toApi(true),
        ]);
    }
}
