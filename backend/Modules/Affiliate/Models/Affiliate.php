<?php

namespace Modules\Affiliate\Models;

use App\Models\User;
use Modules\Tracking\Models\Visitor;
use Illuminate\Database\Eloquent\Model;
use Modules\Tracking\Models\Conversion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Affiliate\Database\factories\AffiliateFactory;

class Affiliate extends Model
{
    protected $fillable = [
        'user_id',
        'ref_code',
        'status',
        'commission_rate',
        'wallet_total',
        'wallet_available',
        'payment_method',
        'payment_data',
    ];

    protected $casts = [
        'payment_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function visitors()
    {
        return $this->hasMany(Visitor::class);
    }

    public function conversions()
    {
        return $this->hasMany(Conversion::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($affiliate) {
            if (empty($affiliate->ref_code)) {
                $affiliate->ref_code = strtoupper(substr(md5(uniqid()), 0, 8));
            }
        });
    }

}

