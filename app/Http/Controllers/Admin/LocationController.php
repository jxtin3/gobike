<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class LocationController extends Controller
{
    /** Statuses that stay on the map until an admin resolves them. */
    private const ALERT_STATUSES = ['emergency', 'responding'];

    /**
     * Show the admin dashboard with the real-time map.
     */
    public function dashboard()
    {
        return view('admin.dashboard');
    }

    /**
     * Return all active locations as JSON for the map widget.
     */
    public function index(): JsonResponse
    {
        $locations = Location::query()
            ->whereNotNull('user_id')
            ->with('user:id,name,mobile')
            ->select([
                'id', 'user_id', 'name', 'role', 'designated_barangay', 'status',
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
                    'id' => $location->user_id ?? $location->id,
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
                'status' => $this->isAlert($current?->status) ? strtolower($current->status) : 'active',
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
                'name'                => $request->user()->name,
                'role'                => 'GoBiker',
                'designated_barangay' => $data['designated_barangay'] ?? $request->user()->barangay,
                'status'              => $this->isAlert($current?->status) ? strtolower($current->status) : 'active',
                'active_start_time'   => now(),
                'active_end_time'     => null,
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
            'status' => $this->isAlert($location->status) ? strtolower($location->status) : 'offline',
            'active_end_time' => now(),
        ]);

        return response()->json(['message' => 'Active mode stopped.']);
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

    /**
     * Admin: mark an emergency as "responding", or resolve it (back to active).
     */
    public function updateStatus(Request $request, Location $location): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:responding,active'],
        ]);

        if (! $this->isAlert($location->status)) {
            return response()->json(['message' => 'This GoBiker has no open emergency.'], 422);
        }

        $location->update(['status' => $data['status']]);

        return response()->json(['message' => 'Status updated.', 'status' => $data['status']]);
    }

    private function isAlert(?string $status): bool
    {
        return in_array(strtolower((string) $status), self::ALERT_STATUSES, true);
    }

    private function currentStatus(Location $location): string
    {
        // Emergency / responding stay visible until an admin resolves them
        if ($this->isAlert($location->status)) {
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

    /**
     * Store a location payload from the admin or seed routine.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:255'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'status' => ['nullable', 'string', 'max:255'],
        ]);

        $location = Location::create([
            'user_id' => auth()->id(),
            'name' => $data['name'] ?? 'System Update',
            'role' => $data['role'] ?? 'Volunteer',
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'status' => $data['status'] ?? 'Active',
        ]);

        return response()->json($location->fresh(), 201);
    }
}