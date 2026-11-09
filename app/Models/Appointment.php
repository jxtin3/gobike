<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A home check-up visit a resident requested; a GoBiker in their barangay takes it. */
class Appointment extends Model
{
    public const STATUSES = [
        'pending' => 'Waiting for the GoBiker to accept',
        'accepted' => 'GoBiker assigned',
        'completed' => 'Visit done',
        'declined' => 'Declined',
        'cancelled' => 'Cancelled',
    ];

    /** Still to be visited. */
    public const OPEN = ['pending', 'accepted'];

    /**
     * How many of these a resident may have at once, each with a different GoBiker. Nothing is
     * cancelled for them when one answers: two GoBikers can both accept the same day, and the
     * resident cancels the one they no longer want.
     */
    public const MAX_OPEN = 3;

    public const WINDOWS = [
        'morning' => 'Morning (8 AM – 12 PM)',
        'afternoon' => 'Afternoon (1 PM – 5 PM)',
    ];

    protected $fillable = [
        'resident_id', 'barangay', 'address', 'preferred_date', 'preferred_window', 'reason',
        'status', 'gobiker_id', 'patient_id', 'decline_note', 'accepted_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'preferred_date' => 'date',
            'accepted_at' => 'datetime',
            'completed_at' => 'datetime',
            'resident_hidden_at' => 'datetime',
        ];
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resident_id');
    }

    public function gobiker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gobiker_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', self::OPEN);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN, true);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function getWindowLabelAttribute(): string
    {
        return self::WINDOWS[$this->preferred_window] ?? ucfirst($this->preferred_window);
    }

    /** The shape both sides of the app read. $forGobiker adds the resident's contact details. */
    public function toApi(bool $forGobiker = false): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'barangay' => $this->barangay,
            'address' => $this->address,
            'preferred_date' => $this->preferred_date?->toDateString(),
            'preferred_window' => $this->preferred_window,
            'window_label' => $this->window_label,
            'reason' => $this->reason,
            'gobiker_id' => $this->gobiker_id,
            'gobiker_name' => $this->gobiker?->name,
            'gobiker_barangay' => $this->gobiker?->barangay,
            'decline_note' => $this->decline_note,
            'created_at' => $this->created_at?->toIso8601String(),
            // When the status last changed: when it was declined or cancelled, which has no
            // timestamp of its own.
            'updated_at' => $this->updated_at?->toIso8601String(),
            'accepted_at' => $this->accepted_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
        ] + ($forGobiker ? [
            'resident_name' => $this->resident?->name,
            'resident_mobile' => $this->resident?->mobile,
            // So the check-up form can be filled in from the resident's account; null without a birthdate.
            'resident_age' => $this->resident?->age,
            'patient_id' => $this->patient_id,
        ] : []);
    }
}
