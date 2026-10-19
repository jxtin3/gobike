<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Picture extends Model
{
    protected $fillable = ['title', 'description', 'image_path', 'uploaded_by'];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isVideo(): bool
    {
        $ext = strtolower(pathinfo($this->image_path, PATHINFO_EXTENSION));
        return in_array($ext, ['mp4', 'webm', 'mov', 'm4v', 'ogg']);
    }
}