<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ouroffersection extends Model
{
    protected $fillable = [
        'title',
        'discount_type',
        'discount_value',
        'description',
        'start_date',
        'end_date',
        'color',
        'image',
        'link',
        'overlay',
        'status',
    ];

    protected $casts = [
        'title' => 'array',
        'description' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
