<?php

namespace Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Wallet\Database\factories\WithdrawMoneyFactory;
use App\Models\User;
use Modules\Bank\Models\Bank;

class WithdrawMoney extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_PAID = 'paid';

    protected $table = 'withdraw_money';
    protected $fillable = [
        'user_id', 'amount', 'bank_id', 'iban', 'account_name', 'datetime', 'payment_type', 'transaction',
        'reference', 'withdraw_money_id', 'status', 'review_note', 'reviewed_by', 'reviewed_at',
    ];
    protected $casts = [
        'bank_id' => 'integer',
        'user_id'   => 'integer',
        'amount'   => 'double',
        'reviewed_at' => 'datetime',
    ];

    public function providers(){
        return $this->belongsTo(User::class, 'user_id','id');
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function bank(){
        return $this->belongsTo(Bank::class, 'bank_id','id');
    }
}
