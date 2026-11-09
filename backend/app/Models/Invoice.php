<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Product\Models\OrderGroup;
use Modules\Booking\Models\Booking;
use App\Models\GiftCard;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cart_ids',
        'gift_ids',
        'product_ids',
        'discount_amount',
        'taxs_service',
        'loyalty_points_discount',
        'final_total',
        'payment_method',
        'collection_details',
    ];

    protected $casts = [
        'cart_ids' => 'array',
        'collection_details' => 'array',
        'gift_ids' => 'array',
        'product_ids' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    

    public function getBookingsAttribute()
    {
        if (empty($this->cart_ids)) {
            return collect();
        }

        return Booking::with('services')
            ->whereIn('id', $this->cart_ids)
            ->get();
    }
    public function getProductsAttribute()
    {
        if (empty($this->product_ids)) {
            return collect();
        }

        return OrderGroup::with('order.orderItems.product')
            ->whereIn('id', $this->product_ids)
            ->get()
            ->flatMap(function ($group) {
                return optional($group->order)->orderItems ?? collect();
            })
            ->map(fn ($item) => $item->product)
            ->filter();
    }


    public function getGiftsAttribute()
    {
        if (empty($this->gift_ids)) {
            return collect();
        }

        return GiftCard::whereIn('id', $this->gift_ids)->get();
    }

}

