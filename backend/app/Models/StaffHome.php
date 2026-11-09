<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffHome extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function services()
    {
        return $this->belongsToMany(ServiceHome::class, 'staff_service_home', 'staff_home_id', 'service_home_id');
    }
    
    public function workingHours()
    {
        return $this->hasMany(StaffWorkingHour::class);
    }

}
