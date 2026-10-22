<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VolunteerApplication extends Model
{
    public const STATUSES = ['pending', 'approved', 'waitlisted', 'rejected'];

    /** Pill colour for each status in the admin. */
    public const STATUS_PILLS = [
        'pending' => 'warn',
        'approved' => 'ok',
        'waitlisted' => 'neutral',
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
    ];

    protected function casts(): array
    {
        return [
            'has_bike' => 'boolean',
            'guardian_consent' => 'boolean',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst((string) $this->application_status);
    }

    public function getStatusPillAttribute(): string
    {
        return self::STATUS_PILLS[$this->application_status] ?? 'neutral';
    }
}
