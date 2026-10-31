<?php

namespace App\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** An emergency request a resident sent with the SOS button. */
class SosRequest extends Model
{
    public const STATUSES = [
        'sent' => 'Waiting for response',
        'responding' => 'Responding',
        'resolved' => 'Resolved',
    ];

    /** Which statuses an admin may move a request to from each status. */
    public const NEXT = [
        'sent' => ['responding', 'resolved'],
        'responding' => ['resolved'],
        'resolved' => [],
    ];

    protected $fillable = [
        'user_id', 'municipality', 'barangay', 'address', 'landmark', 'description',
        'latitude', 'longitude', 'image_path', 'video_path',
        'status', 'handled_by', 'assigned_gobiker_id', 'resolved_by_gobiker_id',
        'responded_at', 'resolved_at', 'resolved_by_resident',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            // Cast: only the assigned GoBiker may see and resolve the request, and that check is a
            // strict comparison against the signed-in id.
            'assigned_gobiker_id' => 'integer',
            'resolved_by_gobiker_id' => 'integer',
            'responded_at' => 'datetime',
            'resolved_at' => 'datetime',
            'resolved_by_resident' => 'boolean',
            'resident_hidden_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /** The GoBiker the admin sent to help. */
    public function assignedGobiker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_gobiker_id');
    }

    /** The GoBiker who marked it resolved: the resident rates them. */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_gobiker_id');
    }

    /** Requests no admin has answered yet: these ring the alarm. */
    public function scopeUnanswered(Builder $query): Builder
    {
        return $query->where('status', 'sent');
    }

    /** Requests still being handled (not resolved). */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', ['sent', 'responding']);
    }

    public function canMoveTo(string $status): bool
    {
        return in_array($status, self::NEXT[$this->status] ?? [], true);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function getImageUrlAttribute(): ?string
    {
        return Media::url($this->image_path);
    }

    public function getVideoUrlAttribute(): ?string
    {
        return Media::url($this->video_path);
    }

    public function getMapUrlAttribute(): ?string
    {
        if ($this->latitude === null || $this->longitude === null) {
            return null;
        }

        return 'https://www.google.com/maps/search/?api=1&query='.$this->latitude.','.$this->longitude;
    }
}
