<?php

namespace Modules\Employee\Models;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\BussinessHour\Models\Shift;
use Modules\Booking\Models\Booking;

class EmployeeRating extends Model
{
    use HasFactory;

    protected $table = 'employee_rating';

    protected $fillable = [
        'employee_id',
        'review_msg',
        'rating',
        'user_id',
        'booking_id',
    ];

    protected static function newFactory()
    {
        return \Modules\Employee\Database\factories\BranchEmployeeFactory::new();
    }

    public function getBranch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function getShift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function branchemployee()
    {
        return $this->belongsTo(BranchEmployee::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }
}
