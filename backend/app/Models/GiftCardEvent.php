<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiftCardEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['gift_card_id', 'event', 'actor_id', 'meta', 'created_at'];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
