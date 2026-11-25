<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Services\Push\PushNotifier;
use App\Support\GobikerEmergency;
use App\Support\Media;
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
            ->with('user:id,name,mobile,photo_path')
            ->select([
                'id', 'user_id', 'name', 'designated_barangay', 'status',
                'latitude', 'longitude', 'accuracy', 'speed', 'position_at',
                'last_seen_at', 'active_start_time',
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
                    // So the admin sees who is on the pin, not just a dot and a name.
                    'photo_url' => Media::url($location->user?->photo_path),
                    'designated_barangay' => $location->designated_barangay,
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                    // How far out the dot may be (metres), and when the phone measured it: the
                    // app can be alive (last_seen_at) while its last good position is older.
                    'accuracy' => $location->accuracy,
                    'speed' => $location->speed,
                    'position_at' => ($location->position_at ?? $location->last_seen_at)?->toIso8601String(),
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
        $this->moveOpenSos($location, $data['status'], $request->user()->id);
        if ($location->user) {
            app(PushNotifier::class)->emergencyAnswered($location->user, $data['status']);
        }

        return response()->json(['message' => 'Status updated.', 'status' => $data['status']]);
    }

    /**
     * The GoBiker's emergency is also an SOS they own (GobikerEmergency): the old buttons on their
     * marker must move it too, or their app would keep saying "Waiting for response".
     */
    private function moveOpenSos(Location $location, string $status, int $adminId): void
    {
        $sos = $location->user ? GobikerEmergency::open($location->user) : null;
        $next = $status === 'active' ? 'resolved' : 'responding';
        if (! $sos || ! $sos->canMoveTo($next)) {
            return;
        }

        $sos->update([
            'status' => $next,
            'handled_by' => $adminId,
            'responded_at' => $sos->responded_at ?? now(),
            'resolved_at' => $next === 'resolved' ? now() : $sos->resolved_at,
        ]);
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
