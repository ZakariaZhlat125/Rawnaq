<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StaffLeavePeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'start_date',
        'end_date',
        'reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * علاقة مع الموظف
     */
    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
}
