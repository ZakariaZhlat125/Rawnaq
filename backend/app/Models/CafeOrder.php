<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CafeOrder extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'total' => 'decimal:2',
    ];

    public function table()
    {
        return $this->belongsTo(CafeTable::class, 'cafe_table_id');
    }

    public function items()
    {
        return $this->hasMany(CafeOrderItem::class);
    }

    public function getStatusLabelAttribute()
    {
        $classes = [
            'pending' => 'bg-soft-warning text-dark',
            'preparing' => 'bg-soft-info',
            'ready' => 'bg-soft-primary',
            'delivered' => 'bg-soft-success',
            'cancelled' => 'bg-soft-danger',
        ];

        $labels = [
            'pending' => __('cafe.status_pending'),
            'preparing' => __('cafe.status_preparing'),
            'ready' => __('cafe.status_ready'),
            'delivered' => __('cafe.status_delivered'),
            'cancelled' => __('cafe.status_cancelled'),
        ];

        return '<span class="badge '.($classes[$this->status] ?? 'bg-soft-secondary').'">'.($labels[$this->status] ?? $this->status).'</span>';
    }
}
