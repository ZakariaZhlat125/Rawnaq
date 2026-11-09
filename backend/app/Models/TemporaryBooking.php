<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemporaryBooking extends Model
{
    use HasFactory;
    
    protected $fillable = ['guest_token', 'booking_data', 'expires_at'];

    protected $casts = [
        'booking_data' => 'array',
        'expires_at' => 'datetime',
    ];

}
