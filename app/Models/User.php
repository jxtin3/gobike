<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Services\Push\PushNotifier;
use App\Support\Age;
use App\Support\Media;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;

#[Fillable(['name', 'email', 'password', 'role', 'status', 'mobile', 'barangay', 'birthdate', 'photo_path', 'google_id', 'has_password', 'notification_prefs', 'municipality_id', 'city_id', 'province_id', 'mfa_secret', 'mfa_enabled_at', 'mfa_backup_codes'])]
#[Hidden(['password', 'remember_token', 'mfa_secret', 'mfa_backup_codes'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the MFA secret (decrypted).
     */
    public function getMfaSecretAttribute(): ?string
    {
        if (! $this->attributes['mfa_secret']) {
            return null;
        }
        return decrypt($this->attributes['mfa_secret']);
    }

    /**
     * Set the MFA secret (encrypted).
     */
    public function setMfaSecretAttribute(?string $value): void
    {
        $this->attributes['mfa_secret'] = $value ? encrypt($value) : null;
    }

    /**
     * Check if MFA is enabled for this user.
     */
    public function hasMfaEnabled(): bool
    {
        return ! is_null($this->mfa_enabled_at);
    }

    /** Whole years old today, from the birthdate; null when they have not given one. */
    public function getAgeAttribute(): ?int
    {
        return Age::fromBirthdate($this->birthdate);
    }

    public function location(): HasOne
    {
        return $this->hasOne(Location::class);
    }

    // Rondas this GoBiker started in the mobile app.
    public function rondas(): HasMany
    {
        return $this->hasMany(Ronda::class);
    }

    // Patient records this GoBiker entered in the mobile app.
    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }

    public function barangayRecord(): BelongsTo
    {
        return $this->belongsTo(Barangay::class, 'barangay', 'name');
    }

    /**
     * Fill municipality_id, city_id and province_id from the barangay string, so the
     * hierarchy columns stay in step with it wherever the barangay is set or changed.
     * A barangay not in the seeded list (possible on legacy rows) clears them.
     */
    public function syncLocationHierarchy(): void
    {
        $barangay = Barangay::where('name', $this->barangay)->first();

        $this->forceFill([
            'municipality_id' => $barangay?->municipality_id,
            'city_id' => $barangay?->city_id,
            'province_id' => $barangay?->province_id,
        ])->saveQuietly();
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    // SOS requests this resident sent.
    public function sosRequests(): HasMany
    {
        return $this->hasMany(SosRequest::class);
    }

    // Home visits this resident requested.
    public function residentAppointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'resident_id');
    }

    // Home visits assigned to this GoBiker.
    public function gobikerAppointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'gobiker_id');
    }

    // Messages this GoBiker sent to the admin.
    public function gobikerMessages(): HasMany
    {
        return $this->hasMany(GobikerMessage::class);
    }

    /** Which push notifications this user wants: the app's switches over the defaults. */
    public function notificationPrefs(): array
    {
        $saved = array_intersect_key($this->notification_prefs ?? [], PushNotifier::PREFS);

        return array_map('boolval', $saved + PushNotifier::PREFS);
    }

    /** What the admin sees for each role. "User" is stored (the app relies on it) but shown as Resident. */
    public const ROLE_LABELS = ['GoBiker' => 'GoBiker', 'User' => 'Resident', 'Admin' => 'Admin'];

    public static function roleLabelFor(?string $role): string
    {
        return self::ROLE_LABELS[$role ?? 'User'] ?? $role;
    }

    // A GoBiker who signed up in the mobile app and is waiting for admin approval.
    public function scopePendingApproval(Builder $query): Builder
    {
        return $query->where('role', 'GoBiker')->where('status', 'Inactive');
    }

    // Approved by an admin since approval emails began, but the email has not reached them yet.
    // Reserved .test addresses (the demo accounts) can never receive mail, so they are left out.
    public function scopeAwaitingApprovalEmail(Builder $query): Builder
    {
        return $query->where('role', 'GoBiker')
            ->where('status', 'Active')
            ->whereNotNull('approved_at')
            ->whereNull('approval_emailed_at')
            ->where('email', 'not like', '%.test');
    }

    /** False for reserved .test addresses (demo accounts), which no mail server delivers to. */
    public function canReceiveEmail(): bool
    {
        return filled($this->email) && ! str_ends_with(strtolower($this->email), '.test');
    }

    public function isPendingApproval(): bool
    {
        return $this->role === 'GoBiker' && $this->status === 'Inactive';
    }

    // After a password change: sign out every other phone, but keep the one making the request.
    public function logoutOtherDevices(): void
    {
        $current = $this->currentAccessToken();

        $this->tokens()
            ->when($current instanceof PersonalAccessToken, fn ($query) => $query->where('id', '!=', $current->id))
            ->delete();
    }

    /** The check-ups GoBikers recorded for this resident. */
    public function checkups(): HasMany
    {
        return $this->hasMany(Patient::class, 'resident_id');
    }

    /**
     * Check-ups recorded before this resident had an account (or under the name and mobile they
     * just switched to) become theirs: same name, ignoring case and spaces, and same mobile.
     * Check-ups that already belong to someone are left alone.
     */
    public function claimCheckups(): void
    {
        if ($this->role !== 'User' || $this->is_admin || blank($this->mobile) || blank($this->name)) {
            return;
        }

        Patient::whereNull('resident_id')
            ->forPerson($this->name, $this->mobile)
            ->update(['resident_id' => $this->id]);
    }

    protected static function booted(): void
    {
        static::saved(function (User $user) {
            if ($user->wasRecentlyCreated || $user->wasChanged(['name', 'mobile', 'role'])) {
                $user->claimCheckups();
            }

            // Keep municipality_id / city_id / province_id in step with the barangay,
            // wherever the barangay was set (sign-up, profiles, admin forms).
            if ($user->wasRecentlyCreated || $user->wasChanged('barangay')) {
                $user->syncLocationHierarchy();
            }
        });

        // Don't leave personal information behind, whichever way the account goes (an admin
        // declining a sign-up or deleting the user, or the user deleting their own account).
        static::deleting(function (User $user) {
            if ($user->photo_path) {
                Storage::disk(Media::disk())->delete($user->photo_path);
            }

            // The live-map row is only unlinked by its foreign key, which would keep the name and
            // last position.
            Location::where('user_id', $user->id)->delete();

            // The SOS rows go with the user (foreign key cascade); their photos and videos don't.
            SosRequest::where('user_id', $user->id)
                ->where(fn ($q) => $q->whereNotNull('image_path')->orWhereNotNull('video_path'))
                ->get(['id', 'image_path', 'video_path'])
                ->each(fn (SosRequest $sos) => $sos->deleteMediaFiles());
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birthdate' => 'date',
            'approved_at' => 'datetime',
            'approval_emailed_at' => 'datetime',
            'mfa_enabled_at' => 'datetime',
            'mfa_backup_codes' => 'array',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'has_password' => 'boolean',
            'notification_prefs' => 'array',
        ];
    }
}
