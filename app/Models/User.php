<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;


#[Fillable(['name', 'email', 'password', 'is_admin', 'role', 'status', 'mobile', 'barangay', 'google_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;
    
    public function location(): HasOne
    {
        return $this->hasOne(Location::class);
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
