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

    /** The most files a volunteer can upload as proof (front and back of an ID, a clearance, ...). */
    public const MAX_ID_FILES = 5;

    /** Accepted proofs of identity and address. A school ID is preferred for students. */
    public const ID_TYPES = [
        'school-id' => 'School ID',
        'valid-id' => 'Other valid ID',
        'barangay-clearance' => 'Barangay clearance',
        'indigency' => 'Certificate of indigency',
        'residency' => 'Certificate of residency',
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
        'id_type',
        'id_paths',
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
        'class_emailed_at',
    ];

    protected function casts(): array
    {
        return [
            'has_bike' => 'boolean',
            'guardian_consent' => 'boolean',
            'id_paths' => 'array',
            'class_emailed_at' => 'datetime',
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

    public function getIdTypeLabelAttribute(): ?string
    {
        return self::ID_TYPES[$this->id_type] ?? null;
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
