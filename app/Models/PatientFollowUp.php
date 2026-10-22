<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Whether GoBikers should keep checking a person, as decided by an admin. */
class PatientFollowUp extends Model
{
    public const DECISIONS = [
        'continue' => 'Keep checking',
        'stop' => 'Stop checking',
    ];

    protected $fillable = ['person_key', 'name', 'contact', 'decision', 'note', 'decided_by', 'decided_at'];

    protected function casts(): array
    {
        return ['decided_at' => 'datetime'];
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function getLabelAttribute(): string
    {
        return self::DECISIONS[$this->decision] ?? ucfirst($this->decision);
    }
}
