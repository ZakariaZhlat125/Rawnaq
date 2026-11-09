<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiftCardRedemption extends Model
{
    protected $fillable = [
        'gift_card_id', 'user_id', 'channel', 'amount', 'wallet_history_id',
        'booking_id', 'invoice_id', 'branch_id', 'staff_id',
    ];

    protected $casts = ['amount' => 'double'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
}
