<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Blog extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'image',
        'status',
        'published_at',
    ];

    protected $casts = [
        'status' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected $appends = [
        'image_url',
    ];

    public function scopePublished($query)
    {
        return $query
            ->where('status', true)
            ->where(function ($query) {
                $query->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function getImageUrlAttribute(): string
    {
        return $this->image ? asset($this->image) : asset('images/default.png');
    }

    public static function makeUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title) ?: Str::slug(Str::limit($title, 40, ''));

        if ($baseSlug === '') {
            $baseSlug = 'blog';
        }

        $slug = $baseSlug;
        $counter = 2;

        while (static::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
