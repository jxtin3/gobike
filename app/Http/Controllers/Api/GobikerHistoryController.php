<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\SosRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * What a GoBiker has finished, for the History screen in their app: home visits they did,
 * declined or that were cancelled, and the emergencies they answered.
 *
 * Read-only and limited to the last 100 of each. Check-ups come from /gobiker/patients and
 * rondas are kept on the phone, so they are not here. Clearing the history is done on the phone
 * (it hides what was there at that moment); nothing in these records is ever deleted from here.
 */
class GobikerHistoryController extends Controller
{
    private const LIMIT = 100;

    public function index(Request $request): JsonResponse
    {
        /** @var User $gobiker */
        $gobiker = $request->user();

        $visits = Appointment::with('resident:id,name,mobile,birthdate', 'gobiker:id,name,barangay')
            ->where('gobiker_id', $gobiker->id)
            ->whereIn('status', ['completed', 'declined', 'cancelled'])
            ->latest('updated_at')
            ->latest('id')
            ->limit(self::LIMIT)
            ->get();

        $sos = SosRequest::with('user:id,name')
            ->where('assigned_gobiker_id', $gobiker->id)
            ->whereIn('status', ['resolved', 'cancelled'])
            ->latest('resolved_at')
            ->latest('id')
            ->limit(self::LIMIT)
            ->get();

        return response()->json([
            'appointments' => $visits->map(fn (Appointment $a) => $a->toApi(true))->values(),
            // No exact location or phone number: these are over, and history does not need them.
            'sos' => $sos->map(fn (SosRequest $s) => [
                'id' => $s->id,
                'status' => $s->api_status,
                'cancelled' => $s->isCancelled(),
                'resident_name' => $s->user?->name,
                'barangay' => $s->barangay,
                'address' => $s->address,
                'landmark' => $s->landmark,
                'description' => $s->description,
                'created_at' => $s->created_at?->toIso8601String(),
                'responded_at' => $s->responded_at?->toIso8601String(),
                'resolved_at' => $s->resolved_at?->toIso8601String(),
            ])->values(),
        ]);
    }
}
