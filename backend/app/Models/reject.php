<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class reject extends Model
{
    use HasFactory;
    protected $fillable = [
        "name",
        "count"
    ];
    protected $casts = [
        'name' => 'array'   
    ];
}
