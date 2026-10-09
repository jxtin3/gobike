<?php

namespace App\Models;
use Illuminate\Support\Str;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'body',
        'image_path',
        'category',
        'is_published',
        'published_at',
        'homepage_position',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'homepage_position' => 'integer',
    ];

    // scope to get published news
    public function scopePublished($query)
    {
        return $query->where('is_published', true)->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    // get summary of the news when the excerpt is empty
    public function getSummaryAttribute(): string
    {
        return $this->excerpt ?: Str::limit(trim(strip_tags($this->body)), 160);
    }
}