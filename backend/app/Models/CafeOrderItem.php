<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CafeOrderItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'item_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(CafeOrder::class, 'cafe_order_id');
    }

    public function item()
    {
        return $this->belongsTo(CafeItem::class, 'cafe_item_id');
    }
}
