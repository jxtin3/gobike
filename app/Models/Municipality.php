<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Municipality extends Model
{
    protected $fillable = ['city_id', 'name', 'code'];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function barangays(): HasMany
    {
        return $this->hasMany(Barangay::class);
    }
}