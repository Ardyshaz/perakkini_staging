<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'old_wp_id',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    protected static function booted(): void
    {
        static::saved(fn () => Post::clearNewsCache());
        static::deleted(fn () => Post::clearNewsCache());
    }
}
