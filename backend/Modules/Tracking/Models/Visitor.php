<?php

namespace Modules\Tracking\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Affiliate\Models\Affiliate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Tracking\Database\factories\VisitorFactory;

class Visitor extends Model
{
    use HasFactory;
    protected $table = 'affiliate_visitors';

    protected $fillable = [
        'affiliate_id',
        'token',
        'ip',
        'user_agent',
    ];

    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function conversions()
    {
        return $this->hasMany(Conversion::class);
    }
}
