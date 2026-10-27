<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A barangay of Bugallon (App\Support\Barangays::ALL). Other tables point here by name, so their
 * `barangay` columns keep holding the plain name the app and the admin pages show.
 */
class Barangay extends Model
{
    protected $fillable = ['name', 'municipality', 'sort_order'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'barangay', 'name');
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class, 'barangay', 'name');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'barangay', 'name');
    }

    public function sosRequests(): HasMany
    {
        return $this->hasMany(SosRequest::class, 'barangay', 'name');
    }
}
