<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\SosRequest;
use Illuminate\Http\JsonResponse;

/**
 * Polled by every admin page so an emergency is noticed even when nobody has the live map open.
 * Two kinds ring the alarm: a GoBiker's emergency button and a resident's SOS request.
 */
class EmergencyAlertController extends Controller
{
    public function index(): JsonResponse
    {
        $gobikers = Location::query()
            ->unansweredEmergencies()
            ->with('user:id,name,mobile')
            ->get()
            ->map(function (Location $location) {
                $since = $location->emergency_at ?? $location->updated_at;

                return [
                    'kind' => 'gobiker',
                    // Changes for every new emergency, so a silenced alarm sounds again for the next one.
                    'key' => $location->id.':'.$since?->getTimestamp(),
                    'location_id' => $location->id,
                    'name' => $location->user?->name ?? $location->name ?? 'Unnamed GoBiker',
                    'mobile' => $location->user?->mobile,
                    'barangay' => $location->designated_barangay,
                    'since' => $since?->toIso8601String(),
                ];
            });

        $residents = SosRequest::query()
            ->unanswered()
            ->with('user:id,name,mobile')
            ->get()
            ->map(fn (SosRequest $sos) => [
                'kind' => 'resident',
                'key' => 'sos-'.$sos->id,
                'sos_id' => $sos->id,
                'name' => $sos->user?->name ?? 'Resident',
                'mobile' => $sos->user?->mobile,
                'barangay' => trim($sos->barangay.' · '.$sos->landmark, ' ·'),
                'since' => $sos->created_at?->toIso8601String(),
                'view_url' => route('admin.operations.sos.show', $sos),
                'respond_url' => route('admin.operations.sos.status', $sos),
            ]);

        return response()->json([
            'emergencies' => $gobikers->concat($residents)->sortBy('since')->values(),
        ]);
    }
}
