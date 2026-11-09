<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Booking\Models\Booking;

class BookingReview extends Model
{
    protected $table = 'booking_reviews';

    protected $fillable = [
        'booking_id',
        'user_id',
        'rating',
        'branch_rating',
        'service_ratings',
        'review_text',
        'is_approved',
        'status',
    ];

    protected $casts = [
        'rating' => 'integer',
        'branch_rating' => 'integer',
        'service_ratings' => 'array',
        'is_approved' => 'boolean',
        'status' => 'boolean',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', 1);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
