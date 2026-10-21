<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VolunteerApplication extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'age',
        'occupation_status',
        'school',
        'barangay',
        'municipality',
        'province',
        'availability',
        'has_bike',
        'motivation',
        'emergency_name',
        'emergency_phone',
        'guardian_name',
        'guardian_phone',
        'guardian_consent',
        'application_status',
    ];

    protected function casts(): array
    {
        return [
            'has_bike'         => 'boolean',
            'guardian_consent' => 'boolean',
        ];
    }
}