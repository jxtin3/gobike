<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A volunteer training class: 20–25 volunteers trained together on one date and venue. */
class VolunteerClass extends Model
{
    protected $fillable = ['starts_on', 'venue', 'notes', 'completed_at'];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function applications(): HasMany
    {
        return $this->hasMany(VolunteerApplication::class);
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    /** "Sat, Nov 14, 2026 · Bugallon Municipal Hall" */
    public function getLabelAttribute(): string
    {
        return $this->starts_on->format('D, M j, Y').' · '.$this->venue;
    }
}
