<?php

namespace App\Services\Wallet;

use App\Events\WalletBalanceChanged;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\WalletHistory;

/**
 * The only place that changes wallet balances.
 *
 * Every movement runs in a transaction with the wallet row locked, refuses to go
 * negative, writes one wallet_histories row with the balances after it, and can be
 * made idempotent with an idempotency_key (a repeated call returns the first row).
 * Amounts are handled in halalas internally to avoid float drift.
 *
 * Options accepted by credit()/debit():
 *   bucket (credit)   'cash' (default) | 'bonus'
 *   buckets (debit)   'any' (default) | 'cash' | 'bonus'
 *   bonus_first       override the wallet_spend_bonus_first setting for this debit
 *   expires_at        bonus credits only
 *   source_type, source_id, message, activity_type, meta (array), idempotency_key, created_by
 *   allow_inactive    let a debit run on a disabled wallet (staff adjustments)
 */
class WalletLedger
{
    public const SOURCES = [
        'topup', 'refund', 'reward', 'points_conversion', 'gift', 'campaign',
        'payment', 'withdraw', 'adjustment', 'expiry', 'payout',
    ];

    public function __construct(private WalletSettings $settings) {}

    public function credit(int $userId, float $amount, string $source, array $options = []): WalletHistory
    {
        $bucket = $options['bucket'] ?? 'cash';
        if (! in_array($bucket, ['cash', 'bonus'], true)) {
            throw new \InvalidArgumentException("Unknown wallet bucket [$bucket].");
        }
        $cents = $this->toCents($amount);
        if ($cents <= 0) {
            throw new \InvalidArgumentException('Wallet credit must be positive.');
        }

        return $this->record($userId, $source, $options, fn () => [
            'direction' => 'credit',
            'cash' => $bucket === 'cash' ? $cents : 0,
            'bonus' => $bucket === 'bonus' ? $cents : 0,
        ]);
    }

    public function debit(int $userId, float $amount, string $source, array $options = []): WalletHistory
    {
        $buckets = $options['buckets'] ?? 'any';
        $cents = $this->toCents($amount);
        if ($cents <= 0) {
            throw new \InvalidArgumentException('Wallet debit must be positive.');
        }

        return $this->record($userId, $source, $options, function (Wallet $wallet) use ($cents, $buckets, $options, $amount) {
            if ((int) $wallet->status === 0 && ! ($options['allow_inactive'] ?? false)) {
                throw new InsufficientWalletBalance($amount, 0);
            }
            $cashAvail = $this->toCents($wallet->cash_balance);
            $bonusAvail = $this->toCents($wallet->bonus_balance);
            $available = match ($buckets) {
                'cash' => $cashAvail,
                'bonus' => $bonusAvail,
                default => $cashAvail + $bonusAvail,
            };
            if ($cents > $available) {
                throw new InsufficientWalletBalance($amount, $available / 100);
            }

            if ($buckets === 'cash') {
                return ['direction' => 'debit', 'cash' => $cents, 'bonus' => 0];
            }
            if ($buckets === 'bonus') {
                return ['direction' => 'debit', 'cash' => 0, 'bonus' => $cents];
            }

            $bonusFirst = $options['bonus_first'] ?? $this->settings->bool('wallet_spend_bonus_first');
            $bonus = $bonusFirst ? min($bonusAvail, $cents) : max(0, $cents - $cashAvail);

            return ['direction' => 'debit', 'cash' => $cents - $bonus, 'bonus' => $bonus];
        });
    }

    /** Amount that can be spent right now, in SAR. */
    public function spendable(int $userId): float
    {
        $wallet = Wallet::where('user_id', $userId)->orderBy('id')->first();
        if (! $wallet || (int) $wallet->status === 0) {
            return 0.0;
        }

        return max(0, ($this->toCents($wallet->cash_balance) + $this->toCents($wallet->bonus_balance) - $this->expiredUnprocessedCents($userId)) / 100);
    }

    public function summary(int $userId): array
    {
        $wallet = Wallet::where('user_id', $userId)->orderBy('id')->first();
        $expired = $this->expiredUnprocessedCents($userId);
        $cash = $wallet ? $this->toCents($wallet->cash_balance) : 0;
        $bonus = $wallet ? max(0, $this->toCents($wallet->bonus_balance) - $expired) : 0;

        $nextExpiry = WalletHistory::where('user_id', $userId)
            ->where('bonus_remaining', '>', 0)
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->orderBy('expires_at')
            ->first(['bonus_remaining', 'expires_at']);

        return [
            'balance' => ($cash + $bonus) / 100,
            'cash_balance' => $cash / 100,
            'bonus_balance' => $bonus / 100,
            'active' => ! $wallet || (int) $wallet->status !== 0,
            'next_expiry' => $nextExpiry ? [
                'amount' => (float) $nextExpiry->bonus_remaining,
                'expires_at' => $nextExpiry->expires_at->toIso8601String(),
            ] : null,
        ];
    }

    /** Remove promotional credit whose lots have expired. Returns the number of wallets touched. */
    public function expireDueBonuses(): int
    {
        $userIds = WalletHistory::where('bonus_remaining', '>', 0)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->distinct()
            ->pluck('user_id');

        foreach ($userIds as $userId) {
            DB::transaction(function () use ($userId) {
                $this->expireDue($this->lockWallet((int) $userId));
            }, 3);
        }

        return $userIds->count();
    }

    private function record(int $userId, string $source, array $options, callable $plan): WalletHistory
    {
        if (! in_array($source, self::SOURCES, true)) {
            throw new \InvalidArgumentException("Unknown wallet source [$source].");
        }
        $key = $options['idempotency_key'] ?? null;

        try {
            return DB::transaction(function () use ($userId, $source, $options, $plan, $key) {
                if ($key && ($existing = WalletHistory::where('idempotency_key', $key)->first())) {
                    return $existing;
                }

                $wallet = $this->lockWallet($userId);
                // Re-check under the lock: a concurrent call with the same key may have just committed.
                if ($key && ($existing = WalletHistory::where('idempotency_key', $key)->first())) {
                    return $existing;
                }
                $this->expireDue($wallet);

                $p = $plan($wallet);
                $sign = $p['direction'] === 'credit' ? 1 : -1;
                $cashAfter = $this->toCents($wallet->cash_balance) + $sign * $p['cash'];
                $bonusAfter = $this->toCents($wallet->bonus_balance) + $sign * $p['bonus'];
                if ($cashAfter < 0 || $bonusAfter < 0) {
                    throw new InsufficientWalletBalance(($p['cash'] + $p['bonus']) / 100, ($this->toCents($wallet->cash_balance) + $this->toCents($wallet->bonus_balance)) / 100);
                }
                if ($p['direction'] === 'debit' && $p['bonus'] > 0) {
                    $this->consumeLots($userId, $p['bonus']);
                }

                $history = $this->write($wallet, $cashAfter, $bonusAfter, [
                    'direction' => $p['direction'],
                    'source' => $source,
                    'cash' => $p['cash'],
                    'bonus' => $p['bonus'],
                    'is_lot' => $p['direction'] === 'credit' && $p['bonus'] > 0,
                ], $options);

                return $history;
            }, 3);
        } catch (QueryException $e) {
            // Two concurrent calls with the same key: the loser hits the unique index.
            if ($key && ($existing = WalletHistory::where('idempotency_key', $key)->first())) {
                return $existing;
            }
            throw $e;
        }
    }

    private function write(Wallet $wallet, int $cashAfter, int $bonusAfter, array $p, array $options): WalletHistory
    {
        $wallet->cash_balance = $cashAfter / 100;
        $wallet->bonus_balance = $bonusAfter / 100;
        $wallet->amount = ($cashAfter + $bonusAfter) / 100;
        $wallet->save();

        $total = $p['cash'] + $p['bonus'];
        $history = WalletHistory::create([
            'datetime' => now(),
            'user_id' => $wallet->user_id,
            'activity_type' => $options['activity_type'] ?? $p['source'],
            'activity_message' => $options['message'] ?? __('wallet_ledger.source.'.$p['source']),
            'activity_data' => json_encode(array_merge($options['meta'] ?? [], [
                'title' => $wallet->title,
                'amount' => (float) $wallet->amount,
                'transaction_type' => $p['direction'],
                'credit_debit_amount' => $total / 100,
            ])),
            'direction' => $p['direction'],
            'source' => $p['source'],
            'source_type' => $options['source_type'] ?? null,
            'source_id' => $options['source_id'] ?? null,
            'amount' => $total / 100,
            'cash_amount' => $p['cash'] / 100,
            'bonus_amount' => $p['bonus'] / 100,
            'balance_after' => (float) $wallet->amount,
            'cash_balance_after' => $cashAfter / 100,
            'bonus_balance_after' => $bonusAfter / 100,
            'bonus_remaining' => $p['is_lot'] ? $p['bonus'] / 100 : null,
            'expires_at' => $p['is_lot'] ? ($options['expires_at'] ?? null) : null,
            'idempotency_key' => $options['idempotency_key'] ?? null,
            'created_by' => $options['created_by'] ?? null,
        ]);

        $event = new WalletBalanceChanged((int) $wallet->user_id, (float) $wallet->amount, $cashAfter / 100, $bonusAfter / 100);
        DB::afterCommit(fn () => event($event));

        return $history;
    }

    private function lockWallet(int $userId): Wallet
    {
        $wallet = Wallet::where('user_id', $userId)->orderBy('id')->lockForUpdate()->first();
        if ($wallet) {
            return $wallet;
        }

        $user = User::find($userId);
        Wallet::create([
            'user_id' => $userId,
            'title' => trim(($user?->first_name ?? '').' '.($user?->last_name ?? '')) ?: 'Wallet',
            'amount' => 0,
            'cash_balance' => 0,
            'bonus_balance' => 0,
            'status' => 1,
        ]);

        return Wallet::where('user_id', $userId)->orderBy('id')->lockForUpdate()->first();
    }

    /** Spend from the earliest-expiring bonus lots first. */
    private function consumeLots(int $userId, int $cents): void
    {
        $lots = WalletHistory::where('user_id', $userId)
            ->where('bonus_remaining', '>', 0)
            ->orderByRaw('CASE WHEN expires_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expires_at')
            ->orderBy('id')
            ->get();

        foreach ($lots as $lot) {
            if ($cents <= 0) {
                break;
            }
            $left = $this->toCents($lot->bonus_remaining);
            $take = min($left, $cents);
            $lot->bonus_remaining = ($left - $take) / 100;
            $lot->save();
            $cents -= $take;
        }
    }

    /** Must run with the wallet row locked. */
    private function expireDue(Wallet $wallet): void
    {
        $lots = WalletHistory::where('user_id', $wallet->user_id)
            ->where('bonus_remaining', '>', 0)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();
        if ($lots->isEmpty()) {
            return;
        }

        $cents = 0;
        foreach ($lots as $lot) {
            $cents += $this->toCents($lot->bonus_remaining);
            $lot->bonus_remaining = 0;
            $lot->save();
        }
        $bonusBefore = $this->toCents($wallet->bonus_balance);
        $cents = min($cents, $bonusBefore);
        if ($cents <= 0) {
            return;
        }

        $this->write($wallet, $this->toCents($wallet->cash_balance), $bonusBefore - $cents, [
            'direction' => 'debit',
            'source' => 'expiry',
            'cash' => 0,
            'bonus' => $cents,
            'is_lot' => false,
        ], ['meta' => ['lot_ids' => $lots->pluck('id')->all()]]);
    }

    private function expiredUnprocessedCents(int $userId): int
    {
        $sum = WalletHistory::where('user_id', $userId)
            ->where('bonus_remaining', '>', 0)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->sum('bonus_remaining');

        return $this->toCents((float) $sum);
    }

    private function toCents(float|int|string|null $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
