<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Services\Push\PushNotifier;
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

#[Fillable(['name', 'email', 'password', 'role', 'status', 'mobile', 'barangay', 'photo_path', 'google_id', 'has_password', 'notification_prefs'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

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

    // A GoBiker who signed up in the mobile app and is waiting for admin approval.
    public function scopePendingApproval(Builder $query): Builder
    {
        return $query->where('role', 'GoBiker')->where('status', 'Inactive');
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
        });

        // Don't leave the profile photo behind in storage, whichever way the account goes
        // (an admin declining a sign-up, or deleting the user).
        static::deleting(function (User $user) {
            if ($user->photo_path) {
                Storage::disk(Media::disk())->delete($user->photo_path);
            }
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
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'has_password' => 'boolean',
            'notification_prefs' => 'array',
        ];
    }
}
