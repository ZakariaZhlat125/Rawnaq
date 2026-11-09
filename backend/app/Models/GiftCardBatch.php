<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiftCardBatch extends Model
{
    protected $fillable = [
        'name', 'source', 'type', 'quantity', 'value_amount', 'requested_services', 'requested_packages',
        'branch_id', 'design', 'expires_at', 'note', 'created_by', 'export_count',
    ];

    protected $casts = [
        'requested_services' => 'array',
        'requested_packages' => 'array',
        'value_amount' => 'double',
        'expires_at' => 'datetime',
    ];

    public function giftCards()
    {
        return $this->hasMany(GiftCard::class, 'batch_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
