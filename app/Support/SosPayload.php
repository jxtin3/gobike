<?php

namespace App\Support;

use App\Models\Location;
use App\Models\SosRequest;

/**
 * What the person who asked for help is told about their SOS: a resident's request, or a
 * GoBiker's own emergency (see GobikerEmergency). Both apps read the same shape.
 */
class SosPayload
{
    /** The request as its owner sees it. */
    public static function forOwner(SosRequest $sos): array
    {
        // Who is coming, or who came. The owner is told this in a push too, but a push can be
        // missed or switched off, so the name belongs in the request itself.
        $responder = $sos->resolvedBy ?? $sos->assignedGobiker;

        return [
            'id' => $sos->id,
            // Older apps only know sent / responding / resolved: see SosRequest::getApiStatusAttribute.
            'status' => $sos->api_status,
            'cancelled' => $sos->isCancelled(),
            'status_label' => $sos->status_label,
            'municipality' => $sos->municipality,
            'barangay' => $sos->barangay,
            'address' => $sos->address,
            'landmark' => $sos->landmark,
            'description' => $sos->description,
            'has_location' => $sos->latitude !== null,
            'image_url' => $sos->image_url,
            'video_url' => $sos->video_url,
            'created_at' => $sos->created_at?->toIso8601String(),
            'responded_at' => $sos->responded_at?->toIso8601String(),
            'resolved_at' => $sos->resolved_at?->toIso8601String(),
            'resolved_by_resident' => (bool) $sos->resolved_by_resident,
            'responder' => $responder ? [
                'name' => $responder->name,
                'barangay' => $responder->barangay,
                'photo_url' => Media::url($responder->photo_path),
            ] : null,
        ];
    }

    /**
     * Where the GoBiker sent to help is right now, for the owner's map.
     *
     * Only while that GoBiker is on the way (the request is "responding"): once it is resolved,
     * cancelled or declined, the GoBiker's position is no longer anyone's business.
     */
    public static function tracking(SosRequest $sos): array
    {
        $gobiker = $sos->status === 'responding' ? $sos->assignedGobiker : null;
        $location = $gobiker ? Location::where('user_id', $gobiker->id)->first() : null;
        $hasPosition = $location?->latitude !== null && $location?->longitude !== null;

        return [
            'sos' => [
                'id' => $sos->id,
                'status' => $sos->api_status,
                'cancelled' => $sos->isCancelled(),
                'latitude' => $sos->latitude,
                'longitude' => $sos->longitude,
            ],
            'tracking' => $gobiker !== null,
            'responder' => $gobiker ? [
                'name' => $gobiker->name,
                'barangay' => $gobiker->barangay,
                'photo_url' => Media::url($gobiker->photo_path),
                // The app is on and reporting. A stale position is still sent, marked offline, so
                // the owner sees where the GoBiker was last rather than nothing at all.
                'online' => (bool) $location?->last_seen_at?->gte(LiveMap::staleBefore()),
                'latitude' => $hasPosition ? $location->latitude : null,
                'longitude' => $hasPosition ? $location->longitude : null,
                'accuracy' => $hasPosition ? $location->accuracy : null,
                'heading' => $hasPosition ? $location->heading : null,
                'position_at' => $hasPosition ? ($location->position_at ?? $location->last_seen_at)?->toIso8601String() : null,
            ] : null,
        ];
    }
}
