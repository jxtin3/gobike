<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
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

        $location = Location::updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'name' => $request->user()->name,
                'role' => 'GoBiker',
                'designated_barangay' => $data['designated_barangay'] ?? $request->user()->barangay,
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

        return response()->json(['message' => 'Active mode stopped.']);
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

        Location::updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'name' => $request->user()->name,
                'role' => 'GoBiker',
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'status' => 'emergency',
                'last_seen_at' => now(),
            ],
        );

        return response()->json(['message' => 'Emergency alert sent to the RHU admin.']);
    }
}
