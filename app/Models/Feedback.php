<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A resident's rating of the GoBiker who answered their SOS or did their check-up. */
class Feedback extends Model
{
    public const KINDS = ['sos', 'checkup'];

    protected $table = 'feedback';

    protected $fillable = ['resident_id', 'gobiker_id', 'kind', 'subject_id', 'rating', 'comment'];

    protected $casts = [
        'rating' => 'integer',
        'subject_id' => 'integer',
    ];

    public function resident(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resident_id');
    }

    public function gobiker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gobiker_id');
    }
}
