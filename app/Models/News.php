<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class News extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'body',
        'image_path',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    // scope to get published news
    public function scopePublished($query)
    {
        return $query->where('is_published', true)->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    /** Published, Scheduled (published with a future date) or Draft. */
    public function getStatusLabelAttribute(): string
    {
        if (! $this->is_published) {
            return 'Draft';
        }

        return $this->published_at?->isFuture() ? 'Scheduled' : 'Published';
    }

    public function getStatusPillAttribute(): string
    {
        return ['Published' => 'ok', 'Scheduled' => 'info', 'Draft' => 'neutral'][$this->status_label];
    }

    // get summary of the news when the excerpt is empty
    public function getSummaryAttribute(): string
    {
        return $this->excerpt ?: Str::limit(trim(strip_tags($this->body)), 160);
    }
}
