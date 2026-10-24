<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Services\Push\PushNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LocationController extends Controller
{
    /**
     * Locations of current GoBikers as JSON for the live map.
     */
    public function index(): JsonResponse
    {
        $locations = Location::query()
            ->whereHas('user', fn ($user) => $user->where('role', 'GoBiker'))
            ->with('user:id,name,mobile')
            ->select([
                'id', 'user_id', 'name', 'designated_barangay', 'status',
                'latitude', 'longitude', 'last_seen_at', 'active_start_time',
                'active_end_time', 'updated_at',
            ])
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (Location $location) {
                $status = $this->currentStatus($location);
                $activeSince = $location->active_start_time;
                $activeUntil = $location->active_end_time ?? now();

                return [
                    'id' => $location->user_id,
                    'location_id' => $location->id,
                    'name' => $location->user?->name ?? $location->name ?? 'Unnamed GoBiker',
                    'mobile' => $location->user?->mobile,
                    'designated_barangay' => $location->designated_barangay,
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                    'status' => $status,
                    'active_since' => $activeSince?->toIso8601String(),
                    'time_active' => $activeSince && $status !== 'offline'
                        ? $this->formatDuration($activeSince, $activeUntil)
                        : '00:00:00',
                    'last_seen_at' => $location->last_seen_at?->toIso8601String(),
                ];
            });

        return response()->json($locations);
    }

    /**
     * Admin: mark an emergency as "responding", or resolve it (back to active).
     */
    public function updateStatus(Request $request, Location $location): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:responding,active'],
        ]);

        if (! Location::isAlertStatus($location->status)) {
            return response()->json(['message' => 'This GoBiker has no open emergency.'], 422);
        }

        $location->update([
            'status' => $data['status'],
            // Resolved: the next emergency from this GoBiker starts fresh.
            'emergency_at' => $data['status'] === 'active' ? null : $location->emergency_at,
        ]);
        if ($location->user) {
            app(PushNotifier::class)->emergencyAnswered($location->user, $data['status']);
        }

        return response()->json(['message' => 'Status updated.', 'status' => $data['status']]);
    }

    private function currentStatus(Location $location): string
    {
        // Emergency / responding stay visible until an admin resolves them
        if (Location::isAlertStatus($location->status)) {
            return strtolower($location->status);
        }

        if (! $location->last_seen_at || $location->last_seen_at->lt(now()->subSeconds(config('app.location_stale_after_seconds')))) {
            return 'offline';
        }

        return strtolower((string) ($location->status ?: 'active'));
    }

    private function formatDuration(Carbon $start, Carbon $end): string
    {
        $seconds = max(0, $start->diffInSeconds($end));

        return sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
    }
}
