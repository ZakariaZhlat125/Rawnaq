<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CafeCategory extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'status' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function items()
    {
        return $this->hasMany(CafeItem::class)->orderBy('sort_order')->orderBy('name');
    }

    public function availableItems()
    {
        return $this->items()->where('status', 'available');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
