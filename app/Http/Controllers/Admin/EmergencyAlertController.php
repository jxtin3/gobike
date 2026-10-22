<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\JsonResponse;

/**
 * Polled by every admin page so an emergency is noticed even when nobody has the live map open.
 */
class EmergencyAlertController extends Controller
{
    public function index(): JsonResponse
    {
        $emergencies = Location::query()
            ->unansweredEmergencies()
            ->with('user:id,name,mobile')
            ->get()
            ->map(function (Location $location) {
                $since = $location->emergency_at ?? $location->updated_at;

                return [
                    // Changes for every new emergency, so a silenced alarm sounds again for the next one.
                    'key' => $location->id.':'.$since?->getTimestamp(),
                    'location_id' => $location->id,
                    'name' => $location->user?->name ?? $location->name ?? 'Unnamed GoBiker',
                    'mobile' => $location->user?->mobile,
                    'barangay' => $location->designated_barangay,
                    'since' => $since?->toIso8601String(),
                ];
            })
            ->sortBy('since')
            ->values();

        return response()->json(['emergencies' => $emergencies]);
    }
}
