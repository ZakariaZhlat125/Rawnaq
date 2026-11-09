<?php

namespace Modules\Promotion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Promotion\Database\factories\CouponFactory;
use App\Models\User;
class Coupon extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'promotions_coupon';

    protected $fillable = ['coupon_code','coupon_type','is_expired', 'timezone', 'services' , 'discount_type', 'discount_percentage', 'discount_amount', 'start_date_time', 'end_date_time', 'promotion_id', 'minimum_booking_amount', 'use_limit'];

    protected $casts = [
        'services' => 'array',
        'minimum_booking_amount' => 'decimal:2',
        'use_limit' => 'integer',
    ];

    protected static function newFactory(): CouponFactory
    {
        //return CouponFactory::new();
    }

    public function promotion()
    {
        return $this->belongsTo(Promotion::class, 'promotion_id');
    }

    public function isCurrentlyExpired(): bool
    {
        return (bool) $this->is_expired || !$this->end_date_time || substr($this->end_date_time, 0, 10) < now('Asia/Riyadh')->toDateString();
    }
    public function userCouponRedeem(){
        return $this->hasMany(UserCouponRedeem::class, 'coupon_id');
    }
    public function userRedeems()
    {
        return $this->hasManyThrough(User::class, UserCouponRedeem::class, 'coupon_id', 'id', 'id', 'user_id');
    }
}
