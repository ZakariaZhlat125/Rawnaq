<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchQuietHours extends Model
{
    protected $table = 'branch_quiet_hours';
    protected $fillable = ['branch_id', 'enabled', 'points', 'hours'];
    protected $casts = ['branch_id' => 'integer', 'enabled' => 'boolean', 'points' => 'integer', 'hours' => 'array'];
}
