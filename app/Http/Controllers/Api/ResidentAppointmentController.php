<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use App\Services\Push\PushNotifier;
use App\Support\Barangays;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** A resident asks for a GoBiker to visit their home for a check-up. */
class ResidentAppointmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $appointments = Appointment::with('gobiker:id,name,barangay')
            ->where('resident_id', $request->user()->id)
            ->whereNull('resident_hidden_at') // cleared from the resident's history
            ->latest()
            ->latest('id')
            ->limit(50)
            ->get();

        return response()->json([
            'appointments' => $appointments->map(fn (Appointment $a) => $a->toApi())->values(),
        ]);
    }

    /** GoBikers the resident can ask for a visit: those in a barangay (their own by default). */
    public function gobikers(Request $request): JsonResponse
    {
        $data = $request->validate([
            'barangay' => ['nullable', 'string', Rule::in(Barangays::ALL)],
        ]);
        $barangay = $data['barangay'] ?? $request->user()->barangay;

        $gobikers = User::where('role', 'GoBiker')
            ->where('status', 'Active')
            ->when($barangay, fn ($q) => $q->where('barangay', $barangay))
            ->withCount(['gobikerAppointments as visits_done' => fn ($q) => $q->where('status', 'completed')])
            ->orderBy('name')
            ->get(['id', 'name', 'barangay']);

        return response()->json([
            'barangay' => $barangay,
            'gobikers' => $gobikers->map(fn (User $g) => [
                'id' => $g->id,
                'name' => $g->name,
                'barangay' => $g->barangay,
                'visits_done' => $g->visits_done,
            ])->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $resident = $request->user();

        if (Appointment::open()->where('resident_id', $resident->id)->exists()) {
            return response()->json([
                'message' => 'You already have a visit request. Cancel it first to ask for a new one.',
            ], 422);
        }

        $data = $request->validate([
            'barangay' => ['required', 'string', Rule::in(Barangays::ALL)],
            'address' => ['required', 'string', 'max:255'],
            'preferred_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:'.now()->addDays(30)->toDateString()],
            'preferred_window' => ['required', Rule::in(array_keys(Appointment::WINDOWS))],
            'reason' => ['required', 'string', 'max:500'],
            // The GoBiker the resident asks; they accept or decline in their app.
            // App versions up to 1.0.10 send none: any GoBiker in the barangay may then accept.
            'gobiker_id' => ['nullable', 'integer', Rule::exists(User::class, 'id')->where('role', 'GoBiker')->where('status', 'Active')],
        ], [
            'gobiker_id.exists' => 'That GoBiker is not available anymore. Please choose another one.',
        ]);

        $appointment = Appointment::create($data + [
            'resident_id' => $resident->id,
            'status' => 'pending',
        ]);
        app(PushNotifier::class)->newAppointmentRequest($appointment->load('resident', 'gobiker'));

        return response()->json([
            'message' => $appointment->gobiker
                ? "Visit requested. {$appointment->gobiker->name} will accept or decline it soon."
                : 'Visit requested. A GoBiker in your barangay will accept it soon.',
            'appointment' => $appointment->toApi(),
        ], 201);
    }

    /** The resident can cancel until the visit is done. */
    public function cancel(Request $request, Appointment $appointment): JsonResponse
    {
        abort_unless($appointment->resident_id === $request->user()->id, 403, 'This is not your request.');

        if (! $appointment->isOpen()) {
            return response()->json(['message' => 'This request can no longer be cancelled.'], 422);
        }

        $appointment->update(['status' => 'cancelled']);
        if ($appointment->gobiker_id) {
            app(PushNotifier::class)->appointmentWithdrawn($appointment->load('resident', 'gobiker'), 'resident');
        }

        return response()->json([
            'message' => 'Visit request cancelled.',
            'appointment' => $appointment->load('gobiker:id,name,barangay')->toApi(),
        ]);
    }
}
