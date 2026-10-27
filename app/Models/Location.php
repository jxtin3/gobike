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
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'name',
        'designated_barangay',
        'latitude',
        'longitude',
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
