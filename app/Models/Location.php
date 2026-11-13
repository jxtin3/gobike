<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Location extends Model
{
    /** Statuses that stay on the map until an admin resolves them. */
    public const ALERT_STATUSES = ['emergency', 'responding'];

    public static function isAlertStatus(?string $status): bool
    {
        return in_array(strtolower((string) $status), self::ALERT_STATUSES, true);
    }

    /** GoBikers' emergencies that no admin has answered yet (not "responding", not resolved). */
    public function scopeUnansweredEmergencies(Builder $query): Builder
    {
        return $query
            ->whereRaw('LOWER(status) = ?', ['emergency'])
            ->whereHas('user', fn ($user) => $user->where('role', 'GoBiker'));
    }

    /**
     * What the app said about a position it sent, turned into the columns to save.
     *
     * The app sends how old the fix is rather than a clock time, so a phone whose clock is wrong
     * cannot make a fresh position look old (or an old one fresh): the time is worked out against
     * the server's own clock. Apps from before 1.0.28 send none of this; their positions are
     * taken as measured just now, with the accuracy unknown.
     *
     * @param  array<string, mixed>  $data  validated: latitude, longitude, and optionally
     *                                      accuracy, heading, speed, age_seconds
     * @return array<string, mixed>
     */
    public static function fixFrom(array $data): array
    {
        $number = fn (string $key) => isset($data[$key]) && $data[$key] >= 0 ? (float) $data[$key] : null;

        return [
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'accuracy' => $number('accuracy'),
            'heading' => $number('heading'),
            'speed' => $number('speed'),
            'position_at' => now()->subSeconds((int) min($number('age_seconds') ?? 0, 7 * 86400)),
        ];
    }

    /**
     * Should this fix move the dot on the map?
     *
     * Indoors or with a weak signal a phone falls back to a cell-tower guess, hundreds of metres
     * out, or to wherever it last knew it was, perhaps hours ago. Drawing those would put the
     * GoBiker somewhere they are not, so the dot stays where it was last really seen. The first
     * position ever is always taken: a rough dot, drawn as rough, beats no dot.
     *
     * @param  array<string, mixed>  $fix  from fixFrom()
     */
    public function acceptsFix(array $fix): bool
    {
        if ($this->latitude === null || $this->longitude === null) {
            return true;
        }
        if ($fix['accuracy'] !== null && $fix['accuracy'] > config('app.location_max_accuracy_m')) {
            return false;
        }
        if ($fix['position_at']->lt(now()->subSeconds((int) config('app.location_max_fix_age_seconds')))) {
            return false;
        }

        // Never step back to a position measured before the one already on the map.
        return $this->position_at === null || $fix['position_at']->gte($this->position_at);
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'name',
        'designated_barangay',
        'latitude',
        'longitude',
        'accuracy',
        'heading',
        'speed',
        'position_at',
        'status',
        'emergency_at',
        'last_seen_at',
        'active_start_time',
        'active_end_time',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'accuracy' => 'float',
        'heading' => 'float',
        'speed' => 'float',
        'position_at' => 'datetime',
        'emergency_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'active_start_time' => 'datetime',
        'active_end_time' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
