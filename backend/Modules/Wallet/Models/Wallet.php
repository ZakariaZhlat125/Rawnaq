<?php

namespace Modules\Wallet\Models;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Wallet\Database\factories\WalletFactory;

/**
 * amount = cash_balance + bonus_balance. Never change these columns directly;
 * use App\Services\Wallet\WalletLedger so every movement is locked and recorded.
 */
class Wallet extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */

    protected $table = 'wallet';
    protected $fillable = [
        'title',
        'user_id',
        'amount',
        'cash_balance',
        'bonus_balance',
        'status'
    ];
    protected $casts = [
        'amount' => 'double',
        'cash_balance' => 'double',
        'bonus_balance' => 'double',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }


}
