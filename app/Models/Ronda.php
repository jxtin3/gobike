<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A ronda (patrol) a GoBiker started in the mobile app. */
class Ronda extends Model
{
    protected $fillable = ['user_id', 'barangay', 'started_at', 'ended_at'];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Length in minutes, or null while the ronda is still open. */
    public function durationMinutes(): ?int
    {
        return $this->ended_at ? (int) $this->started_at->diffInMinutes($this->ended_at) : null;
    }
}
