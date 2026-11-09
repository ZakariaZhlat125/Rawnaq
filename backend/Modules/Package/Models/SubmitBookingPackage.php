<?php

namespace Modules\Package\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubmitBookingPackage extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'booking_id',
        'package_id',
        'branch_id',
        'user_id',
        'booking_date',
        'booking_time',
        'notes',
        'total_price',
        'status',
    ];

    protected $casts = [
        'booking_id'    => 'integer',
        'package_id'    => 'integer',
        'branch_id'     => 'integer',
        'user_id'       => 'integer',
        'total_price'   => 'double',
    ];


    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
