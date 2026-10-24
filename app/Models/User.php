<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
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

#[Fillable(['name', 'email', 'password', 'role', 'status', 'mobile', 'barangay', 'google_id'])]
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
        ];
    }
}
