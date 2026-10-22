<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Ronda;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GoBiker mobile app: share location, start/stop a ronda and send an emergency alert.
 */
class GobikerLocationController extends Controller
{
    public function updateLocation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $current = Location::where('user_id', $request->user()->id)->first();

        $location = Location::updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'name' => $request->user()->name,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                // keep an open emergency/responding status until an admin resolves it
                'status' => Location::isAlertStatus($current?->status) ? strtolower($current->status) : 'active',
                'last_seen_at' => now(),
            ],
        );

        return response()->json(['message' => 'Location updated.', 'location' => $location]);
    }

    public function startActiveSession(Request $request): JsonResponse
    {
        $data = $request->validate([
            'designated_barangay' => ['nullable', 'string', 'max:255'],
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
                'role' => 'GoBiker',
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
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $current = Location::where('user_id', $request->user()->id)->first();

        // Pressing the button again while still waiting keeps the original time; anything else
        // (first alert, or a new one after the admin was already responding) is a new emergency
        // and sounds the admin alarm again.
        $stillWaiting = strtolower((string) $current?->status) === 'emergency' && $current->emergency_at;

        Location::updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'name' => $request->user()->name,
                'role' => 'GoBiker',
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
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
