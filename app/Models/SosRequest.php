<?php

namespace App\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/** An emergency request a resident sent with the SOS button. */
class SosRequest extends Model
{
    public const STATUSES = [
        'sent' => 'Waiting for response',
        'responding' => 'Responding',
        'resolved' => 'Resolved',
        // The resident no longer needs help. Only they can set it; the admin and the GoBiker resolve.
        'cancelled' => 'Cancelled',
    ];

    /** Which statuses an admin may move a request to from each status. */
    public const NEXT = [
        'sent' => ['responding', 'resolved'],
        'responding' => ['resolved'],
        'resolved' => [],
        'cancelled' => [],
    ];

    protected $fillable = [
        'user_id', 'municipality', 'barangay', 'address', 'landmark', 'description',
        'latitude', 'longitude', 'location_accuracy', 'location_recorded_at', 'image_path', 'video_path',
        'status', 'handled_by', 'assigned_gobiker_id', 'resolved_by_gobiker_id',
        'responded_at', 'resolved_at', 'resolved_by_resident',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'location_accuracy' => 'float',
            'location_recorded_at' => 'datetime',
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

    /** The resident can still withdraw it: nobody has closed it yet. */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['sent', 'responding'], true);
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * The status the mobile apps are told. Apps released before "cancelled" existed treat any
     * status other than "resolved" as an emergency that is still open, so a cancelled request is
     * reported to them as resolved; newer apps also get `cancelled: true` and tell the two apart.
     */
    public function getApiStatusAttribute(): string
    {
        return $this->isCancelled() ? 'resolved' : $this->status;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    /**
     * SOS photos and videos can show a person in distress, so they are kept on the private disk
     * and opened only by admins, through admin.operations.sos.media. Files uploaded before that
     * change were on the public media disk; the move migration copied them over.
     */
    public static function mediaDisk(): string
    {
        return (string) config('filesystems.private_disk');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? route('admin.operations.sos.media', [$this, 'image']) : null;
    }

    public function getVideoUrlAttribute(): ?string
    {
        return $this->video_path ? route('admin.operations.sos.media', [$this, 'video']) : null;
    }

    /**
     * Removes the photo and video files (by default this request's own), from the public media
     * disk too in case a file from before the move was left there.
     *
     * @param  array<int, string|null>|null  $paths
     */
    public function deleteMediaFiles(?array $paths = null): void
    {
        $paths = array_values(array_filter($paths ?? [$this->image_path, $this->video_path]));
        if ($paths === []) {
            return;
        }

        foreach (array_unique([self::mediaDisk(), Media::disk()]) as $disk) {
            Storage::disk($disk)->delete($paths);
        }
    }

    public function getMapUrlAttribute(): ?string
    {
        if ($this->latitude === null || $this->longitude === null) {
            return null;
        }

        return 'https://www.google.com/maps/search/?api=1&query='.$this->latitude.','.$this->longitude;
    }
}
