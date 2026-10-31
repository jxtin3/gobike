<?php

namespace App\Support;

use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Collection;

/** Who appears on the live map in the mobile app: everyone is limited to their own barangay. */
class LiveMap
{
    /** The barangay a GoBiker covers: the one picked for the current ronda, else their own. */
    public static function gobikerBarangay(User $gobiker): ?string
    {
        return $gobiker->location?->designated_barangay ?? $gobiker->barangay;
    }

    /**
     * GoBikers whose app sent a position recently, in a barangay.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function onlineGobikers(?string $barangay, ?int $exceptUserId = null): Collection
    {
        if ($barangay === null) {
            return collect();
        }

        return Location::query()
            ->with('user:id,name,barangay')
            ->whereHas('user', fn ($user) => $user->where('role', 'GoBiker')->where('status', 'Active'))
            ->where('last_seen_at', '>=', now()->subSeconds((int) config('app.location_stale_after_seconds', 30)))
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($exceptUserId, fn ($q) => $q->where('user_id', '!=', $exceptUserId))
            ->get()
            ->filter(fn (Location $location) => ($location->designated_barangay ?? $location->user?->barangay) === $barangay)
            ->map(fn (Location $location) => [
                'id' => $location->user_id,
                'name' => $location->user?->name ?? $location->name,
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'status' => Location::isAlertStatus($location->status) ? strtolower($location->status) : 'active',
            ])
            ->values();
    }

    /**
     * GoBikers the admin can send to an SOS, online ones first.
     *
     * @param  string|null  $barangay  limit to the GoBikers covering this barangay; null: all of them
     * @return Collection<int, array<string, mixed>>
     */
    public static function gobikersFor(?string $barangay): Collection
    {
        $stale = now()->subSeconds((int) config('app.location_stale_after_seconds', 30));

        return User::query()
            ->with('location:id,user_id,designated_barangay,last_seen_at,latitude,longitude')
            ->where('role', 'GoBiker')
            ->where('status', 'Active')
            ->get(['id', 'name', 'barangay'])
            ->filter(fn (User $user) => $barangay === null || self::covers($user, $barangay))
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'barangay' => $user->location?->designated_barangay ?? $user->barangay,
                'online' => (bool) $user->location?->last_seen_at?->gte($stale),
                'latitude' => $user->location?->latitude,
                'longitude' => $user->location?->longitude,
            ])
            ->sortBy([['online', 'desc'], ['name', 'asc']])
            ->values();
    }

    /**
     * The same list, ordered by how near each GoBiker actually is to the emergency.
     *
     * The barangays have no coordinates and no record of which ones adjoin, so "nearby" cannot be
     * worked out from the names. Where both the SOS and the GoBiker's app have sent a position,
     * the real distance is used instead; GoBikers with no position sort last.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function gobikersForAssignment(?float $latitude, ?float $longitude, ?string $barangay): Collection
    {
        return self::gobikersFor($barangay)
            ->map(function (array $gobiker) use ($latitude, $longitude) {
                $gobiker['distance_km'] = self::distanceKm(
                    $latitude, $longitude,
                    $gobiker['latitude'], $gobiker['longitude'],
                );

                return $gobiker;
            })
            ->sortBy([
                ['online', 'desc'],
                // sortBy puts nulls first, so unknown distances are pushed past every known one.
                [fn (array $a, array $b) => ($a['distance_km'] ?? INF) <=> ($b['distance_km'] ?? INF), 'asc'],
                ['name', 'asc'],
            ])
            ->values();
    }

    /** Does this GoBiker cover $barangay: the one picked for the current ronda, else their own. */
    private static function covers(User $gobiker, string $barangay): bool
    {
        return ($gobiker->location?->designated_barangay ?? $gobiker->barangay) === $barangay;
    }

    /** Kilometres between two points (haversine), or null when either one is unknown. */
    private static function distanceKm(?float $lat1, ?float $lng1, ?float $lat2, ?float $lng2): ?float
    {
        if ($lat1 === null || $lng1 === null || $lat2 === null || $lng2 === null) {
            return null;
        }

        $earthRadiusKm = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return round($earthRadiusKm * 2 * asin(min(1.0, sqrt($a))), 2);
    }
}
