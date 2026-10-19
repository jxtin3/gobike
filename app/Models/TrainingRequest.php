<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingRequest extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'tier',
        'message',
        'status',
    ];
}
