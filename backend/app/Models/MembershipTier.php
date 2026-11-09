<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_en',
        'name_ar',
        'level',
        'min_points',
        'discount_percentage',
        'benefits',
    ];

    protected $casts = [
        'benefits' => 'array',
    ];

    public function userMemberships()
    {
        return $this->hasMany(UserMembership::class);
    }
}
