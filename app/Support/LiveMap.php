<?php

namespace App\Support;

use App\Models\Barangay;
use App\Models\Location;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/** Who appears on the live map in the mobile app: the online GoBikers of the viewer's town. */
class LiveMap
{
    /** The barangay a GoBiker covers: the one picked for the current ronda, else their own. */
    public static function gobikerBarangay(User $gobiker): ?string
    {
        return $gobiker->location?->designated_barangay ?? $gobiker->barangay;
    }

    /**
     * A position older than this is stale: the GoBiker's app is closed or has lost signal, and
     * they count as offline. One definition, used by the maps and by assignment alike, so the
     * admin can never be shown somebody the server will then refuse.
     */
    public static function staleBefore(): CarbonInterface
    {
        return now()->subSeconds((int) config('app.location_stale_after_seconds', 30));
    }

    /** Is this GoBiker's app on and reporting right now? */
    public static function isOnline(?int $userId): bool
    {
        return $userId !== null && Location::where('user_id', $userId)
            ->where('last_seen_at', '>=', self::staleBefore())
            ->exists();
    }

    /**
     * The town each barangay is in, both lowercased: its linked municipality, else the old text
     * column (the seeded Bugallon barangays only have the text). Unknown barangays are left out.
     *
     * @param  array<int, string|null>  $barangayNames
     * @return array<string, string>
     */
    public static function townsFor(array $barangayNames): array
    {
        $names = collect($barangayNames)->filter(fn ($name) => filled($name))->unique()->values();
        if ($names->isEmpty()) {
            return [];
        }

        return Barangay::query()
            ->with('municipality:id,name')
            ->whereIn('name', $names->all())
            ->get(['name', 'municipality', 'municipality_id'])
            ->mapWithKeys(fn (Barangay $b) => [mb_strtolower(trim($b->name)) => mb_strtolower(trim((string) self::townName($b)))])
            ->filter(fn (string $town) => $town !== '')
            ->all();
    }

    /** The town a barangay is in, as it is written, or null when unknown. */
    public static function townOf(?string $barangay): ?string
    {
        if (blank($barangay)) {
            return null;
        }

        $row = Barangay::query()->with('municipality:id,name')->where('name', $barangay)
            ->first(['name', 'municipality', 'municipality_id']);
        $town = $row ? trim((string) self::townName($row)) : '';

        return $town === '' ? null : $town;
    }

    /**
     * GoBikers whose app is open (it sent a position in the last few seconds), in the same town
     * as $barangay, whatever barangay they cover in it. They need not be on a ronda.
     *
     * When the town of $barangay is unknown, only the GoBikers of that exact barangay appear.
     * Residents never appear: they never send a position.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function onlineGobikers(?string $barangay, ?int $exceptUserId = null): Collection
    {
        if (blank($barangay)) {
            return collect();
        }

        $locations = Location::query()
            ->with('user:id,name,barangay,photo_path')
            ->whereHas('user', fn ($user) => $user->where('role', 'GoBiker')->where('status', 'Active'))
            ->where('last_seen_at', '>=', self::staleBefore())
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($exceptUserId, fn ($q) => $q->where('user_id', '!=', $exceptUserId))
            ->get();

        $covered = fn (Location $location) => $location->designated_barangay ?? $location->user?->barangay;
        $key = fn (?string $name) => mb_strtolower(trim((string) $name));

        $towns = self::townsFor([$barangay, ...$locations->map($covered)->all()]);
        $viewerTown = $towns[$key($barangay)] ?? null;

        return $locations
            ->filter(fn (Location $location) => $viewerTown !== null
                ? ($towns[$key($covered($location))] ?? null) === $viewerTown
                : $key($covered($location)) === $key($barangay))
            ->map(fn (Location $location) => [
                'id' => $location->user_id,
                'name' => $location->user?->name ?? $location->name,
                'barangay' => $covered($location),
                'photo_url' => $location->user?->photo_path ? Media::url($location->user->photo_path) : null,
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'accuracy' => $location->accuracy,
                'position_at' => ($location->position_at ?? $location->last_seen_at)?->toIso8601String(),
                'status' => Location::isAlertStatus($location->status) ? strtolower($location->status) : 'active',
                'on_ronda' => $location->active_start_time !== null && $location->active_end_time === null,
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
        $stale = self::staleBefore();

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
     * The GoBikers the admin may actually send: the online ones, nearest first.
     *
     * The barangays have no coordinates and no record of which ones adjoin, so "nearby" cannot be
     * worked out from the names. Where both the SOS and the GoBiker's app have sent a position,
     * the real distance is used instead; GoBikers with no position sort last.
     *
     * $exceptUserIds leaves people out: a GoBiker in trouble is never offered as their own helper,
     * and GoBikers already on the way to this SOS are not offered again.
     *
     * @param  list<int>  $exceptUserIds
     * @return Collection<int, array<string, mixed>>
     */
    public static function gobikersForAssignment(?float $latitude, ?float $longitude, ?string $barangay, array $exceptUserIds = []): Collection
    {
        return self::gobikersFor($barangay)
            ->reject(fn (array $gobiker) => in_array($gobiker['id'], $exceptUserIds, true))
            // Only GoBikers who are actually online: someone whose app is closed cannot be told
            // an emergency is waiting, so the admin must not be offered them in the first place.
            ->filter(fn (array $gobiker) => $gobiker['online'])
            ->map(function (array $gobiker) use ($latitude, $longitude) {
                $gobiker['distance_km'] = self::distanceKm(
                    $latitude, $longitude,
                    $gobiker['latitude'], $gobiker['longitude'],
                );

                return $gobiker;
            })
            ->sortBy([
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

    /** The relation and the old text column share the name "municipality": read each explicitly. */
    private static function townName(Barangay $barangay): ?string
    {
        return $barangay->getRelation('municipality')?->name ?? $barangay->getAttribute('municipality');
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
