<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** An announcement from the admin to every GoBiker. */
class Announcement extends Model
{
    protected $fillable = ['title', 'body', 'is_active', 'expires_at', 'created_by'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    /** What GoBikers see: switched on and not past its end date. */
    public function scopeLive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /** Live, Expired or Hidden (switched off by the admin). */
    public function getStatusLabelAttribute(): string
    {
        return match (true) {
            ! $this->is_active => 'Hidden',
            $this->isExpired() => 'Expired',
            default => 'Live',
        };
    }

    public function getStatusPillAttribute(): string
    {
        return ['Live' => 'ok', 'Expired' => 'neutral', 'Hidden' => 'neutral'][$this->status_label];
    }
}
