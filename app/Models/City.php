<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    protected $fillable = ['province_id', 'name', 'code'];

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
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