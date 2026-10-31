<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A resident's rating of the GoBiker who answered their SOS or did their check-up. */
class Feedback extends Model
{
    public const KINDS = ['sos', 'checkup'];

    /** What the resident was rating. */
    public const KIND_LABELS = [
        'sos' => 'Emergency SOS',
        'checkup' => 'Check-up',
    ];

    protected $table = 'feedback';

    protected $fillable = ['resident_id', 'gobiker_id', 'kind', 'subject_id', 'rating', 'comment'];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'subject_id' => 'integer',
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

    public function getKindLabelAttribute(): string
    {
        return self::KIND_LABELS[$this->kind] ?? $this->kind;
    }

    /** A rating with no stars means the resident chose to skip it. */
    public function getWasSkippedAttribute(): bool
    {
        return $this->rating === null;
    }

    /** Good news green, middling amber, poor red, a skip neutral. */
    public function getRatingPillAttribute(): string
    {
        return match (true) {
            $this->rating === null => 'neutral',
            $this->rating >= 4 => 'ok',
            $this->rating === 3 => 'warn',
            default => 'bad',
        };
    }
}
