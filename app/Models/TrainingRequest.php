<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingRequest extends Model
{
    public const STATUSES = ['pending', 'contacted', 'completed', 'cancelled'];

    /** Pill colour for each status in the admin. */
    public const STATUS_PILLS = [
        'pending' => 'warn',
        'contacted' => 'info',
        'completed' => 'ok',
        'cancelled' => 'neutral',
    ];

    /** The plans people can request now. Free group training is volunteering instead. */
    public const OFFERED = ['standard', 'advance'];

    public const TIERS = [
        'standard' => 'Standard (₱1,500)',
        'advance' => 'Advance (₱2,500)',
        // Older requests only.
        'basic' => 'Basic (no longer offered)',
    ];

    public const TIER_PILLS = [
        'standard' => 'info',
        'advance' => 'warn',
        'basic' => 'neutral',
    ];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'tier',
        'preferred_date',
        'preferred_window',
        'message',
        'id_paths',
        'status',
    ];

    /** Most ID photos a person can upload (front, back, a second ID, ...). */
    public const MAX_ID_FILES = 5;

    protected function casts(): array
    {
        return [
            'preferred_date' => 'date',
            'id_paths' => 'array',
        ];
    }

    /** "Sat, Nov 7 · Morning", or null for older requests without a date. */
    public function getScheduleLabelAttribute(): ?string
    {
        if (! $this->preferred_date) {
            return null;
        }

        return $this->preferred_date->format('D, M j, Y')
            .($this->preferred_window ? ' · '.ucfirst($this->preferred_window) : '');
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst((string) $this->status);
    }

    public function getStatusPillAttribute(): string
    {
        return self::STATUS_PILLS[$this->status] ?? 'neutral';
    }

    public function getTierLabelAttribute(): string
    {
        return self::TIERS[$this->tier] ?? ucfirst((string) $this->tier);
    }

    public function getTierPillAttribute(): string
    {
        return self::TIER_PILLS[$this->tier] ?? 'neutral';
    }
}
