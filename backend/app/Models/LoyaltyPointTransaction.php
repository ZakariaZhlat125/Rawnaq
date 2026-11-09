<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyPointTransaction extends Model
{
    protected $table = 'loyalty_points_transactions';

    protected $fillable = [
        'user_id',
        'action',
        'points',
        'balance_after',
        'source',
        'source_id',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
    
        public function user()
    {
        return $this->belongsTo(User::class);
    }
    
}
