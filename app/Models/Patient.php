<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Patient extends Model
{
    protected $fillable = [
        'user_id', 'barangay', 'name', 'address', 'contact', 'age',
        'sys', 'dia', 'pulse', 'resp', 'temp', 'height', 'weight', 'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'age' => 'integer',
            'sys' => 'integer',
            'dia' => 'integer',
            'pulse' => 'integer',
            'resp' => 'integer',
            'temp' => 'float',
            'height' => 'float',
            'weight' => 'float',
        ];
    }

    /** The GoBiker who recorded this check-up. */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}