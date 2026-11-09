<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class GiftDesign extends Model
{
    protected $fillable = [
        'slug', 'name_ar', 'name_en', 'occasion', 'background', 'foreground', 'icon',
        'image_path', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    /** Shape used by the website (same keys as the old src/data/gifts.js DESIGNS). */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->slug,
            'n' => $this->name_ar,
            'n_en' => $this->name_en,
            'occasion' => $this->occasion,
            'bg' => $this->background,
            'fg' => $this->foreground,
            'icon' => $this->icon,
            'image' => $this->image_url,
        ];
    }
}
