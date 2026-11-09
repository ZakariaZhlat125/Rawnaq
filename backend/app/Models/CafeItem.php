<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CafeItem extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'price' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    protected $appends = [
        'image_url',
    ];

    public function category()
    {
        return $this->belongsTo(CafeCategory::class, 'cafe_category_id');
    }

    public function orderItems()
    {
        return $this->hasMany(CafeOrderItem::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function getImageUrlAttribute()
    {
        return $this->image ? asset($this->image) : asset('images/default.jpg');
    }
}
