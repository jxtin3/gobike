<?php

namespace App\Support;

use App\Models\Location;
use App\Models\SosRequest;
use App\Models\SosResponder;
use App\Models\User;
use Illuminate\Support\Collection;

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
        // missed or switched off, so the names belong in the request itself. Several GoBikers can
        // be sent: all of them are listed, the one sent last first; once resolved, only the one
        // who resolved it. `responder` stays a single person for older apps.
        $active = self::activeGobikers($sos);
        $responders = $sos->resolvedBy ? collect([$sos->resolvedBy]) : $active;
        $responder = $sos->resolvedBy ?? $active->first() ?? $sos->assignedGobiker;

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
            'responder' => $responder ? self::person($responder) : null,
            'responders' => $responders->map(fn (User $u) => self::person($u))->values()->all(),
        ];
    }

    /**
     * Where the GoBikers sent to help are right now, for the owner's map.
     *
     * Only while they are on the way (the request is "responding"): once it is resolved,
     * cancelled or declined, their positions are no longer anyone's business. `responder` is the
     * one sent last, for older apps; `responders` lists every one of them.
     */
    public static function tracking(SosRequest $sos): array
    {
        $gobikers = $sos->status === 'responding' ? self::activeGobikers($sos) : collect();
        $locations = Location::whereIn('user_id', $gobikers->pluck('id')->all())->get()->keyBy('user_id');
        $tracked = $gobikers->map(fn (User $g) => self::tracked($g, $locations->get($g->id)))->values();

        return [
            'sos' => [
                'id' => $sos->id,
                'status' => $sos->api_status,
                'cancelled' => $sos->isCancelled(),
                'latitude' => $sos->latitude,
                'longitude' => $sos->longitude,
            ],
            'tracking' => $tracked->isNotEmpty(),
            'responder' => $tracked->first(),
            'responders' => $tracked->all(),
        ];
    }

    /**
     * The GoBikers still on the way, the one sent last first.
     *
     * @return Collection<int, User>
     */
    private static function activeGobikers(SosRequest $sos): Collection
    {
        $rows = $sos->relationLoaded('responders')
            ? $sos->responders
                ->filter(fn (SosResponder $r) => $r->isResponding())
                ->sortBy([
                    fn (SosResponder $a, SosResponder $b) => ($b->assigned_at?->getTimestamp() ?? 0) <=> ($a->assigned_at?->getTimestamp() ?? 0),
                    fn (SosResponder $a, SosResponder $b) => $b->id <=> $a->id,
                ])
            : $sos->activeResponders()->with('gobiker:id,name,barangay,photo_path')->get();

        return $rows->map(fn (SosResponder $r) => $r->gobiker)->filter()->values();
    }

    private static function person(User $user): array
    {
        return [
            'name' => $user->name,
            'barangay' => $user->barangay,
            'photo_url' => Media::url($user->photo_path),
        ];
    }

    private static function tracked(User $gobiker, ?Location $location): array
    {
        $hasPosition = $location?->latitude !== null && $location?->longitude !== null;

        return [
            ...self::person($gobiker),
            // The app is on and reporting. A stale position is still sent, marked offline, so
            // the owner sees where the GoBiker was last rather than nothing at all.
            'online' => (bool) $location?->last_seen_at?->gte(LiveMap::staleBefore()),
            'latitude' => $hasPosition ? $location->latitude : null,
            'longitude' => $hasPosition ? $location->longitude : null,
            'accuracy' => $hasPosition ? $location->accuracy : null,
            'heading' => $hasPosition ? $location->heading : null,
            'position_at' => $hasPosition ? ($location->position_at ?? $location->last_seen_at)?->toIso8601String() : null,
        ];
    }
}
