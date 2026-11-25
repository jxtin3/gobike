<?php

namespace App\Support;

use App\Models\Barangay;
use App\Models\Location;
use App\Models\SosRequest;
use App\Models\User;

/**
 * A GoBiker's emergency is an SOS request they own, so it gets everything a resident's SOS has:
 * the admin can send another GoBiker to help, the GoBiker sees who is coming and can cancel.
 *
 * Their Location row keeps saying 'emergency' / 'responding' as well: that is what turns their
 * marker red on the maps, and what app versions from before this read. syncLocation() keeps the
 * two in step.
 */
class GobikerEmergency
{
    /** A closed emergency is still reported to the app this long, so it can say how it ended. */
    private const RECENT_MINUTES = 30;

    /** The GoBiker's emergency nobody has closed yet. */
    public static function open(User $gobiker): ?SosRequest
    {
        return SosRequest::where('user_id', $gobiker->id)->open()->latest()->latest('id')->first();
    }

    /** The open emergency, else the one closed in the last few minutes. */
    public static function latest(User $gobiker): ?SosRequest
    {
        return self::open($gobiker) ?? SosRequest::where('user_id', $gobiker->id)
            ->whereIn('status', ['resolved', 'cancelled'])
            ->where('resolved_at', '>=', now()->subMinutes(self::RECENT_MINUTES))
            ->latest('resolved_at')
            ->latest('id')
            ->first();
    }

    /**
     * The GoBiker pressed Emergency: open their SOS at where their marker is, or, while one is
     * still open, move it there. Null when their barangay is not a known one (the SOS needs it):
     * the Location alone then carries the emergency, as it did before.
     */
    public static function raise(User $gobiker, Location $location): ?SosRequest
    {
        $sos = self::open($gobiker);

        if ($sos) {
            $changes = self::position($location);
            // The admin had only pressed "Responding" and sent nobody: pressing again rings the
            // alarm again. With a GoBiker on the way it just shows them the new position.
            if ($sos->status === 'responding' && ! $sos->assigned_gobiker_id) {
                $changes['status'] = 'sent';
            }
            $sos->update($changes);
        } else {
            $barangay = $location->designated_barangay ?? $gobiker->barangay;
            if (blank($barangay) || ! Barangay::where('name', $barangay)->exists()) {
                return null;
            }

            $sos = SosRequest::create(self::position($location) + [
                'user_id' => $gobiker->id,
                'status' => 'sent',
                'barangay' => $barangay,
                'municipality' => LiveMap::townOf($barangay) ?? Barangays::MUNICIPALITY,
                'address' => null,
                'landmark' => '',
            ]);
        }

        self::syncLocation($sos);

        return $sos;
    }

    /** The GoBiker moved while waiting: the helper must see where they are now. */
    public static function follow(Location $location): void
    {
        SosRequest::where('user_id', $location->user_id)->open()->update(self::position($location));
    }

    /** Make the GoBiker's marker say what their SOS says. Nothing for a resident's SOS. */
    public static function syncLocation(SosRequest $sos): void
    {
        if (! $sos->fromGobiker()) {
            return;
        }

        $location = Location::where('user_id', $sos->user_id)->first();
        if (! $location) {
            return;
        }

        $location->update(match ($sos->status) {
            'sent', 'declined' => ['status' => 'emergency', 'emergency_at' => $location->emergency_at ?? now()],
            'responding' => ['status' => 'responding', 'emergency_at' => $location->emergency_at ?? now()],
            default => ['status' => 'active', 'emergency_at' => null],
        });
    }

    /** @return array<string, mixed> */
    private static function position(Location $location): array
    {
        return [
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
            'location_accuracy' => $location->accuracy,
            'location_recorded_at' => $location->position_at ?? $location->last_seen_at ?? now(),
        ];
    }
}
