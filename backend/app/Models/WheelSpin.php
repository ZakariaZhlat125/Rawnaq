<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WheelSpin extends Model
{
    protected $fillable = [
        'ip_address',
        'user_id',
        'wheel_id',
        'won',
        'reward_type',
        'reward_value',
        'credited',
        'credited_at',
    ];

    protected $casts = [
        'won' => 'boolean',
        'credited' => 'boolean',
        'reward_value' => 'float',
        'credited_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function wheel()
    {
        return $this->belongsTo(Wheel::class);
    }
}
