<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Services\Push\PushNotifier;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;

#[Fillable(['name', 'email', 'password', 'role', 'status', 'mobile', 'barangay', 'google_id', 'schedule_days', 'schedule_start', 'schedule_end', 'has_password', 'notification_prefs'])]
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

    /** Days a GoBiker can be scheduled on, in week order. */
    public const SCHEDULE_DAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

    /**
     * The GoBiker's ronda schedule for the app, e.g. ['days' => ['Mon', 'Wed'], 'start' => '08:00',
     * 'end' => '12:00'], or null when the admin has not set one.
     */
    public function schedule(): ?array
    {
        if (empty($this->schedule_days) || ! $this->schedule_start || ! $this->schedule_end) {
            return null;
        }

        return [
            // Week order, whatever order they were saved in.
            'days' => array_values(array_intersect(self::SCHEDULE_DAYS, $this->schedule_days)),
            'start' => substr($this->schedule_start, 0, 5),
            'end' => substr($this->schedule_end, 0, 5),
        ];
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

    /**
     * The check-ups GoBikers recorded for this person. Check-ups carry no user ID, so the person
     * is recognised the same way the admin does it: same name (ignoring case and spaces) and the
     * same contact number. Without a mobile number nothing can be matched safely.
     */
    public function checkups(): Builder
    {
        if (blank($this->mobile) || blank($this->name)) {
            return Patient::query()->whereRaw('1 = 0');
        }

        return Patient::query()->forPerson($this->name, $this->mobile);
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
            'schedule_days' => 'array',
            'has_password' => 'boolean',
            'notification_prefs' => 'array',
        ];
    }
}
