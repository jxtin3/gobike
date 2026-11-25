<?php

namespace App\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/** An emergency request a resident sent with the SOS button, or a GoBiker's own emergency. */
class SosRequest extends Model
{
    public const STATUSES = [
        'sent' => 'Waiting for response',
        'responding' => 'Responding',
        'resolved' => 'Resolved',
        // The resident no longer needs help. Only they can set it; the admin and the GoBiker resolve.
        'cancelled' => 'Cancelled',
        // The assigned GoBiker cannot go; they are required to give a reason. The admin then
        // reassigns or marks resolved.
        'declined' => 'Declined by GoBiker',
    ];

    /** Which statuses an admin may move a request to from each status. */
    public const NEXT = [
        'sent' => ['responding', 'resolved'],
        'responding' => ['resolved', 'declined'],
        'resolved' => [],
        'cancelled' => [],
        'declined' => ['responding', 'resolved'], // admin can reassign or close
    ];

    protected $fillable = [
        'user_id', 'municipality', 'barangay', 'address', 'landmark', 'description',
        'latitude', 'longitude', 'location_accuracy', 'location_recorded_at', 'image_path', 'video_path',
        'status', 'handled_by', 'assigned_gobiker_id', 'resolved_by_gobiker_id', 'declined_by_gobiker_id',
        'responded_at', 'resolved_at', 'resolved_by_resident', 'declined_at', 'decline_reason',
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
            'declined_by_gobiker_id' => 'integer',
            'responded_at' => 'datetime',
            'resolved_at' => 'datetime',
            'resolved_by_resident' => 'boolean',
            'resident_hidden_at' => 'datetime',
            'declined_at' => 'datetime',
        ];
    }

    /**
     * Keeps the GoBikers-sent list in step with assigned_gobiker_id when code (older paths, test
     * fixtures) sets that column directly: the GoBiker set there gets their row.
     */
    protected static function booted(): void
    {
        static::saved(function (SosRequest $sos) {
            $gobikerId = $sos->assigned_gobiker_id;
            if (! $gobikerId || ! ($sos->wasRecentlyCreated || $sos->wasChanged('assigned_gobiker_id'))) {
                return;
            }

            $declined = $sos->status === 'declined' && $sos->declined_by_gobiker_id === $gobikerId;

            SosResponder::firstOrCreate(
                ['sos_request_id' => $sos->id, 'gobiker_id' => $gobikerId],
                [
                    'status' => $declined ? SosResponder::DECLINED : SosResponder::RESPONDING,
                    'assigned_by' => $sos->handled_by,
                    'assigned_at' => $sos->responded_at ?? now(),
                    'declined_at' => $declined ? $sos->declined_at : null,
                    'decline_reason' => $declined ? $sos->decline_reason : null,
                ],
            );
        });
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

    /** The GoBiker who turned the assignment down (with their reason). */
    public function declinedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'declined_by_gobiker_id');
    }

    /** Every GoBiker the admin sent, including those who declined. */
    public function responders(): HasMany
    {
        return $this->hasMany(SosResponder::class, 'sos_request_id');
    }

    /** The GoBikers still on the way, the most recently sent first. */
    public function activeResponders(): HasMany
    {
        return $this->responders()
            ->where('status', SosResponder::RESPONDING)
            ->latest('assigned_at')
            ->latest('id');
    }

    /** This GoBiker was sent and is still on the way. */
    public function isRespondedBy(int $userId): bool
    {
        return $this->activeResponders()->where('gobiker_id', $userId)->exists();
    }

    /**
     * assigned_gobiker_id is "the newest GoBiker still on the way", for app versions and pages
     * that know only one. Left as it is when nobody is (it then names who declined last).
     */
    public function syncAssignedGobiker(): void
    {
        $newest = $this->activeResponders()->value('gobiker_id');
        if ($newest) {
            $this->assigned_gobiker_id = (int) $newest;
        }
    }

    /** Requests no admin has answered yet: these ring the alarm. */
    public function scopeUnanswered(Builder $query): Builder
    {
        return $query->where('status', 'sent');
    }

    /** Requests still needing someone (not resolved, cancelled — or declined and waiting to be reassigned). */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', ['sent', 'responding', 'declined']);
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

    /** A GoBiker's own emergency (see GobikerEmergency), not a resident's request. */
    public function fromGobiker(): bool
    {
        return $this->user?->role === 'GoBiker';
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
