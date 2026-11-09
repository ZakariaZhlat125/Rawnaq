<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
        protected $fillable = [
        'customer_name',
        'mobile_no',
        'neighborhood',
        'branch',
        'gender',
        'service_group_id',
        'service_id',
        'date',
        'time',
        'staff_id',
    ];
    
public function customer()
{
    return $this->belongsTo(User::class, 'customer_id');
}

public function serviceGroup()
{
    return $this->belongsTo(ServiceGroupHome::class, 'service_group_id');
}

public function service()
{
    return $this->belongsTo(ServiceHome::class, 'service_id');
}

public function staff()
{
    return $this->belongsTo(StaffHome::class, 'staff_id');
}

}
