<?php

namespace Modules\Wallet\Models;


use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Wallet\Database\factories\WalletHistoryFactory;

class WalletHistory extends Model
{
    use HasFactory;
    protected $table = 'wallet_histories';
    protected $fillable = [
        'datetime', 'user_id', 'activity_type', 'activity_message', 'activity_data',
        'direction', 'source', 'source_type', 'source_id', 'amount', 'cash_amount', 'bonus_amount',
        'balance_after', 'cash_balance_after', 'bonus_balance_after', 'bonus_remaining', 'expires_at',
        'idempotency_key', 'created_by',
    ];

    protected $casts = [
        'user_id'   => 'integer',
        'source_id' => 'integer',
        'amount' => 'double',
        'cash_amount' => 'double',
        'bonus_amount' => 'double',
        'balance_after' => 'double',
        'cash_balance_after' => 'double',
        'bonus_balance_after' => 'double',
        'bonus_remaining' => 'double',
        'expires_at' => 'datetime',
    ];

    public function providers(){
        return $this->belongsTo(User::class, 'user_id','id');
    }

    public function wallet(){
        return $this->belongsTo(Wallet::class, 'user_id','user_id');
    }
}
