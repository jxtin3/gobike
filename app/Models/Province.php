<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    protected $fillable = ['name', 'region', 'code'];

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    public function municipalities(): HasMany
    {
        return $this->hasMany(Municipality::class);
    }

    public function barangays(): HasMany
    {
        return $this->hasMany(Barangay::class);
    }
}