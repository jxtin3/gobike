<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use App\Services\Push\PushNotifier;
use App\Support\Media;
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
            'barangay' => ['nullable', 'string', Rule::exists('barangays', 'name')],
        ]);
        $barangay = $data['barangay'] ?? $request->user()->barangay;

        $gobikers = User::where('role', 'GoBiker')
            ->where('status', 'Active')
            ->when($barangay, fn ($q) => $q->where('barangay', $barangay))
            ->withCount(['gobikerAppointments as visits_done' => fn ($q) => $q->where('status', 'completed')])
            ->orderBy('name')
            ->get(['id', 'name', 'barangay', 'photo_path']);
        $askAgain = Appointment::askAgainTimes($request->user()->id);

        return response()->json([
            'barangay' => $barangay,
            'gobikers' => $gobikers->map(fn (User $g) => [
                'id' => $g->id,
                'name' => $g->name,
                'barangay' => $g->barangay,
                'photo_url' => $g->photo_path ? Media::url($g->photo_path) : null,
                'visits_done' => $g->visits_done,
                // Set while this GoBiker's decline is recent: the app greys them out until then.
                'ask_again_at' => $askAgain->get($g->id)?->toIso8601String(),
            ])->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $resident = $request->user();

        // Up to MAX_OPEN at once, so a resident who is waiting on one GoBiker can ask another.
        $open = Appointment::open()->where('resident_id', $resident->id)->get(['id', 'gobiker_id']);
        if ($open->count() >= Appointment::MAX_OPEN) {
            return response()->json([
                'message' => 'You can have up to '.Appointment::MAX_OPEN.' open visit requests. Cancel one first to ask for a new one.',
            ], 422);
        }

        $data = $request->validate([
            'barangay' => ['required', 'string', Rule::exists('barangays', 'name')],
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

        // Never the same GoBiker twice: they would get two requests from one person.
        $chosen = $data['gobiker_id'] ?? null;
        if ($chosen !== null && $open->contains('gobiker_id', (int) $chosen)) {
            $name = User::find($chosen)?->name ?? 'that GoBiker';

            return response()->json([
                'message' => "You already asked {$name}. Wait for their answer, or cancel that request first.",
            ], 422);
        }

        // A GoBiker who said no can't be asked again straight away.
        $askAgainAt = $chosen !== null ? Appointment::askAgainTimes($resident->id)->get((int) $chosen) : null;
        if ($askAgainAt !== null) {
            $name = User::find($chosen)?->name ?? 'That GoBiker';
            $minutes = now()->diffInMinutes($askAgainAt, true);
            $hours = (int) ceil($minutes / 60);
            $wait = match (true) {
                $minutes < 60 => 'less than an hour',
                $hours === 1 => '1 hour',
                default => "{$hours} hours",
            };

            return response()->json([
                'message' => "{$name} declined your last request. You can ask them again in {$wait}, or ask another GoBiker.",
            ], 422);
        }

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
