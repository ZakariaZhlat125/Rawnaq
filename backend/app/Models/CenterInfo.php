<?php

namespace App\Models;

class CenterInfo extends BaseModel
{
    protected $casts = [
        'status' => 'boolean',
    ];

    protected $appends = [
        'cover_image_url',
        'profile_image_url',
        'public_url',
        'qr_url',
    ];

    public static function current(): self
    {
        return self::firstOrCreate([], [
            'title' => 'مركز رونق',
            'about' => 'مركز رونق يقدم خدمات العناية والجمال بتجربة مريحة ومنظمة.',
            'status' => true,
        ]);
    }

    public function getCoverImageUrlAttribute()
    {
        return $this->cover_image ? asset($this->cover_image) : asset('images/samilogo.png');
    }

    public function getProfileImageUrlAttribute()
    {
        return $this->profile_image ? asset($this->profile_image) : asset('images/samilogo.png');
    }

    public function getPublicUrlAttribute()
    {
        return route('center.info');
    }

    public function getQrUrlAttribute()
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=320x320&data='.urlencode($this->public_url);
    }
}
