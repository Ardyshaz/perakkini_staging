<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;

class Post extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'gallery',
        'status',
        'is_featured',
        'views',
        'published_at',
        'old_wp_id',
    ];

    protected function casts(): array
    {
        return [
            'gallery' => 'array',
            'is_featured' => 'boolean',
            'views' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::clearNewsCache());
        static::deleted(fn () => static::clearNewsCache());
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeLatestPublished(Builder $query): Builder
    {
        return $query->published()->latest('published_at');
    }

    public function scopePopular(Builder $query): Builder
    {
        return $query->published()->orderByDesc('views')->latest('published_at');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public static function clearNewsCache(): void
    {
        foreach (['news.home.payload', 'news.sidebar'] as $key) {
            Cache::forget($key);
        }
    }
}
