<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMembership extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'membership_tier_id',
        'points_balance',
        'total_points_earned',
        'qr_code_token',
        'apple_wallet_pass_id',
        'google_wallet_pass_id',
        'is_active',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tier()
    {
        return $this->belongsTo(MembershipTier::class, 'membership_tier_id');
    }
}
