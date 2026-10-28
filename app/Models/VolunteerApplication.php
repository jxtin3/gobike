<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VolunteerApplication extends Model
{
    /**
     * pending: new, not reviewed yet · waiting: approved, waiting for a full class ·
     * scheduled: in a class with a date · trained: finished the class · rejected.
     */
    public const STATUSES = ['pending', 'waiting', 'scheduled', 'trained', 'rejected'];

    public const STATUS_LABELS = [
        'pending' => 'New',
        'waiting' => 'Waiting for class',
        'scheduled' => 'Class scheduled',
        'trained' => 'Trained',
        'rejected' => 'Rejected',
    ];

    /** Pill colour for each status in the admin. */
    public const STATUS_PILLS = [
        'pending' => 'warn',
        'waiting' => 'info',
        'scheduled' => 'brand',
        'trained' => 'ok',
        'rejected' => 'bad',
    ];

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'age',
        'occupation_status',
        'school',
        'barangay',
        'municipality',
        'province',
        'availability',
        'has_bike',
        'motivation',
        'emergency_name',
        'emergency_phone',
        'guardian_name',
        'guardian_phone',
        'guardian_consent',
        'application_status',
        'volunteer_class_id',
    ];

    protected function casts(): array
    {
        return [
            'has_bike' => 'boolean',
            'guardian_consent' => 'boolean',
        ];
    }

    public function volunteerClass(): BelongsTo
    {
        return $this->belongsTo(VolunteerClass::class);
    }

    /** Signed up and not in a class yet: these fill the next class. */
    public function scopeInQueue(Builder $query): Builder
    {
        return $query->whereIn('application_status', ['pending', 'waiting'])->whereNull('volunteer_class_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->application_status] ?? ucfirst((string) $this->application_status);
    }

    public function getStatusPillAttribute(): string
    {
        return self::STATUS_PILLS[$this->application_status] ?? 'neutral';
    }
}
