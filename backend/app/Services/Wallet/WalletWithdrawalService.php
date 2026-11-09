<?php

namespace App\Services\Wallet;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Bank\Models\Bank;
use Modules\Wallet\Models\WithdrawMoney;

/**
 * Cash withdrawals: the amount is held (debited from cash only) when the request is
 * made, released back if staff reject it, and staff mark it paid after the transfer.
 * Promotional balance can never be withdrawn.
 */
class WalletWithdrawalService
{
    public function __construct(private WalletLedger $ledger, private WalletSettings $settings) {}

    public function request(int $userId, float $amount, array $bank): WithdrawMoney
    {
        if (! $this->settings->bool('wallet_withdrawal_enabled')) {
            throw ValidationException::withMessages(['amount' => __('wallet_ledger.withdrawal_disabled')]);
        }
        $min = $this->settings->float('wallet_withdrawal_min');
        if ($amount < $min || $amount <= 0) {
            throw ValidationException::withMessages(['amount' => __('wallet_ledger.withdrawal_min', ['min' => $min])]);
        }

        $bankId = $bank['bank_id'] ?? null;
        if ($bankId && ! Bank::where('id', $bankId)->where('user_id', $userId)->exists()) {
            throw ValidationException::withMessages(['bank_id' => __('wallet_ledger.withdrawal_bank_required')]);
        }
        if (! $bankId && (empty($bank['iban']) || empty($bank['account_name']))) {
            throw ValidationException::withMessages(['iban' => __('wallet_ledger.withdrawal_bank_required')]);
        }

        return DB::transaction(function () use ($userId, $amount, $bank, $bankId) {
            $withdrawal = WithdrawMoney::create([
                'user_id' => $userId,
                'amount' => round($amount, 2),
                'bank_id' => $bankId,
                'iban' => $bankId ? null : strtoupper(preg_replace('/\s+/', '', $bank['iban'])),
                'account_name' => $bankId ? null : $bank['account_name'],
                'datetime' => now(),
                'payment_type' => 'bank_transfer',
                'status' => $this->settings->bool('wallet_withdrawal_requires_approval')
                    ? WithdrawMoney::STATUS_PENDING
                    : WithdrawMoney::STATUS_APPROVED,
            ]);

            try {
                $this->ledger->debit($userId, $amount, 'withdraw', [
                    'buckets' => 'cash',
                    'activity_type' => 'withdrawal',
                    'source_type' => 'withdrawal',
                    'source_id' => $withdrawal->id,
                    'idempotency_key' => 'withdraw:'.$withdrawal->id,
                ]);
            } catch (InsufficientWalletBalance $e) {
                throw ValidationException::withMessages(['amount' => $e->getMessage()]);
            }

            return $withdrawal;
        });
    }

    public function approve(WithdrawMoney $withdrawal, int $staffId, ?string $note = null): WithdrawMoney
    {
        return $this->transition($withdrawal, [WithdrawMoney::STATUS_PENDING], WithdrawMoney::STATUS_APPROVED, $staffId, $note);
    }

    public function markPaid(WithdrawMoney $withdrawal, int $staffId, string $reference, ?string $note = null): WithdrawMoney
    {
        return $this->transition($withdrawal, [WithdrawMoney::STATUS_PENDING, WithdrawMoney::STATUS_APPROVED], WithdrawMoney::STATUS_PAID, $staffId, $note, $reference);
    }

    /** Rejecting releases the held amount back to the customer's cash balance. */
    public function reject(WithdrawMoney $withdrawal, int $staffId, ?string $note = null): WithdrawMoney
    {
        return DB::transaction(function () use ($withdrawal, $staffId, $note) {
            $locked = $this->transition($withdrawal, [WithdrawMoney::STATUS_PENDING, WithdrawMoney::STATUS_APPROVED], WithdrawMoney::STATUS_REJECTED, $staffId, $note);
            $this->ledger->credit((int) $locked->user_id, (float) $locked->amount, 'withdraw', [
                'activity_type' => 'withdrawal_reversal',
                'message' => __('wallet_ledger.withdraw_status.rejected'),
                'source_type' => 'withdrawal',
                'source_id' => $locked->id,
                'idempotency_key' => 'withdraw:'.$locked->id.':reversal',
                'created_by' => $staffId,
            ]);

            return $locked;
        });
    }

    private function transition(WithdrawMoney $withdrawal, array $from, string $to, int $staffId, ?string $note, ?string $reference = null): WithdrawMoney
    {
        return DB::transaction(function () use ($withdrawal, $from, $to, $staffId, $note, $reference) {
            $locked = WithdrawMoney::whereKey($withdrawal->id)->lockForUpdate()->firstOrFail();
            if (! in_array($locked->status, $from, true)) {
                throw ValidationException::withMessages(['status' => __('wallet_ledger.withdrawal_not_pending')]);
            }
            $locked->update(array_filter([
                'status' => $to,
                'reviewed_by' => $staffId,
                'reviewed_at' => now(),
                'review_note' => $note,
                'reference' => $reference,
            ], fn ($v) => $v !== null));

            $this->notify($locked);

            return $locked;
        });
    }

    /** Same inbox format as CustomerActivityObserver, so it shows in the customer's notifications. */
    private function notify(WithdrawMoney $withdrawal): void
    {
        $user = User::find($withdrawal->user_id);
        $title = __('wallet_ledger.source.withdraw').': '.__('wallet_ledger.withdraw_status.'.$withdrawal->status);
        $user?->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => self::class,
            'data' => ['subject' => $title, 'data' => [
                'type' => $title,
                'message' => $title.' — '.number_format((float) $withdrawal->amount, 2).' ر.س',
                'notification_group' => 'wallet',
                'id' => $withdrawal->id,
                'event' => 'wallet.withdrawal.'.$withdrawal->status,
                'amount' => (float) $withdrawal->amount,
                'status' => $withdrawal->status,
            ]],
        ]);
    }
}
