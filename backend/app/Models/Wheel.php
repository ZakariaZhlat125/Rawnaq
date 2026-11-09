<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wheel extends Model
{
    use HasFactory;
    protected $fillable = [
        "type",
        "reward_value",
    ];
}
