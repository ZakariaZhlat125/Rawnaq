<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class CafeTable extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'status' => 'boolean',
    ];

    protected $appends = [
        'order_url',
        'qr_url',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($table) {
            if (empty($table->code)) {
                do {
                    $code = 'TBL-'.Str::upper(Str::random(8));
                } while (self::where('code', $code)->exists());

                $table->code = $code;
            }
        });
    }

    public function orders()
    {
        return $this->hasMany(CafeOrder::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function getOrderUrlAttribute()
    {
        return url('/cafe/menu?table='.$this->id);
    }

    public function getQrUrlAttribute()
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&data='.urlencode($this->order_url);
    }
}
