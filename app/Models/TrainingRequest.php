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

    public const TIERS = [
        'basic' => 'Basic (Free)',
        'standard' => 'Standard (₱499)',
        'advance' => 'Advance (₱999)',
    ];

    public const TIER_PILLS = [
        'basic' => 'ok',
        'standard' => 'info',
        'advance' => 'warn',
    ];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'tier',
        'message',
        'status',
    ];

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
