<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceHome extends Model
{
    use HasFactory;


    protected $guarded = [];

    public function staff()
    {
        return $this->belongsToMany(StaffHome::class, 'staff_service_home', 'service_home_id', 'staff_home_id');
    }



}
