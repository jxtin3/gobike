<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A GoBiker the admin sent to an SOS. Several can be sent to one; each can decline on their own. */
class SosResponder extends Model
{
    public const RESPONDING = 'responding';

    public const DECLINED = 'declined';

    protected $fillable = [
        'sos_request_id', 'gobiker_id', 'status', 'assigned_by', 'assigned_at', 'declined_at', 'decline_reason',
    ];

    protected function casts(): array
    {
        return [
            'sos_request_id' => 'integer',
            'gobiker_id' => 'integer',
            'assigned_at' => 'datetime',
            'declined_at' => 'datetime',
        ];
    }

    public function sos(): BelongsTo
    {
        return $this->belongsTo(SosRequest::class, 'sos_request_id');
    }

    public function gobiker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gobiker_id');
    }

    public function isResponding(): bool
    {
        return $this->status === self::RESPONDING;
    }
}
