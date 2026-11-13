<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Ronda;
use App\Support\Barangays;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * GoBiker mobile app: share location, start/stop a ronda and send an emergency alert.
 */
class GobikerLocationController extends Controller
{
    /**
     * A position from the phone. Only the coordinates are required: the rest says how good the
     * fix is (see Location::fixFrom) and is loosely checked on purpose, because a phone reporting
     * an odd number must never be refused its place on the map.
     */
    private const FIX_RULES = [
        'latitude' => ['required', 'numeric', 'between:-90,90'],
        'longitude' => ['required', 'numeric', 'between:-180,180'],
        'accuracy' => ['nullable', 'numeric'],
        'heading' => ['nullable', 'numeric'],
        'speed' => ['nullable', 'numeric'],
        'age_seconds' => ['nullable', 'numeric'],
    ];

    public function updateLocation(Request $request): JsonResponse
    {
        $data = $request->validate(self::FIX_RULES);

        $current = Location::where('user_id', $request->user()->id)->first();
        $fix = Location::fixFrom($data);
        // A rough or old fix still proves the app is on, so the GoBiker stays online; it just
        // does not move their dot.
        $moved = ! $current || $current->acceptsFix($fix);

        $location = Location::updateOrCreate(
            ['user_id' => $request->user()->id],
            ($moved ? $fix : []) + [
                'name' => $request->user()->name,
                // keep an open emergency/responding status until an admin resolves it
                'status' => Location::isAlertStatus($current?->status) ? strtolower($current->status) : 'active',
                'last_seen_at' => now(),
            ],
        );

        return response()->json(['message' => 'Location updated.', 'moved' => $moved, 'location' => $location]);
    }

    public function startActiveSession(Request $request): JsonResponse
    {
        $data = $request->validate([
            'designated_barangay' => ['nullable', 'string', Rule::in(Barangays::ALL)],
        ]);

        $current = Location::where('user_id', $request->user()->id)->first();
        $barangay = $data['designated_barangay'] ?? $request->user()->barangay;

        // Keep a history of rondas for the admin reports. A ronda left open (e.g. the app was
        // closed without stopping) ends when the next one starts.
        $this->closeOpenRondas($request->user()->id);
        Ronda::create(['user_id' => $request->user()->id, 'barangay' => $barangay, 'started_at' => now()]);

        $location = Location::updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'name' => $request->user()->name,
                'designated_barangay' => $barangay,
                'status' => Location::isAlertStatus($current?->status) ? strtolower($current->status) : 'active',
                'active_start_time' => now(),
                'active_end_time' => null,
                'last_seen_at' => now(), // shows as active on the map right away
            ],
        );

        return response()->json([
            'message' => 'Active mode started.',
            'active_start_time' => $location->active_start_time,
        ]);
    }

    public function stopActiveSession(Request $request): JsonResponse
    {
        $location = Location::where('user_id', $request->user()->id)->first();

        if (! $location) {
            return response()->json(['message' => 'GoBiker location not found.'], 404);
        }

        $location->update([
            'status' => Location::isAlertStatus($location->status) ? strtolower($location->status) : 'offline',
            'active_end_time' => now(),
        ]);

        $this->closeOpenRondas($request->user()->id);

        return response()->json(['message' => 'Active mode stopped.']);
    }

    /**
     * What the admin has done about this GoBiker's emergency, so the app can tell them:
     * 'emergency' (sent, nobody answered yet), 'responding' (the admin is on it)
     * or null (no open emergency, e.g. after the admin resolved it).
     */
    public function emergencyStatus(Request $request): JsonResponse
    {
        $status = Location::where('user_id', $request->user()->id)->value('status');

        return response()->json([
            'status' => Location::isAlertStatus($status) ? strtolower($status) : null,
        ]);
    }

    public function currentSession(Request $request): JsonResponse
    {
        $location = Location::where('user_id', $request->user()->id)->first();

        $active = $location
            && $location->active_start_time
            && ! $location->active_end_time;

        return response()->json([
            'active' => (bool) $active,
            'active_start_time' => $active
                ? $location->active_start_time->toIso8601String()
                : null,
        ]);
    }

    public function sendEmergency(Request $request): JsonResponse
    {
        $data = $request->validate(self::FIX_RULES);

        $current = Location::where('user_id', $request->user()->id)->first();
        $fix = Location::fixFrom($data);

        // Pressing the button again while still waiting keeps the original time; anything else
        // (first alert, or a new one after the admin was already responding) is a new emergency
        // and sounds the admin alarm again.
        $stillWaiting = strtolower((string) $current?->status) === 'emergency' && $current->emergency_at;

        Location::updateOrCreate(
            ['user_id' => $request->user()->id],
            // A rough guess must not replace a good position the map already has.
            (! $current || $current->acceptsFix($fix) ? $fix : []) + [
                'name' => $request->user()->name,
                'status' => 'emergency',
                'emergency_at' => $stillWaiting ? $current->emergency_at : now(),
                'last_seen_at' => now(),
            ],
        );

        return response()->json(['message' => 'Emergency alert sent to the RHU admin.']);
    }

    private function closeOpenRondas(int $userId): void
    {
        Ronda::where('user_id', $userId)->whereNull('ended_at')->update(['ended_at' => now()]);
    }
}
