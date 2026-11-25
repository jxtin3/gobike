<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GobikerMessage extends Model
{
    protected $fillable = ['user_id', 'message', 'from_admin', 'is_read', 'reply_from_admin_id', 'reply_message', 'replied_at'];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'from_admin' => 'boolean',
            'replied_at' => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function adminReply(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reply_from_admin_id');
    }
}
