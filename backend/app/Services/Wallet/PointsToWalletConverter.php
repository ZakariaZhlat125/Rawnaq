<?php

namespace App\Services\Wallet;

use App\Models\LoyaltyPoint;
use App\Models\LoyaltyPointTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Convert loyalty points into wallet balance at the checkout rate (point_value),
 * writing both ledgers in one transaction.
 */
class PointsToWalletConverter
{
    public function __construct(private WalletLedger $ledger, private WalletSettings $settings) {}

    /** @return array{points: int, amount: float, points_balance: int} */
    public function convert(int $userId, int $points): array
    {
        if (! $this->settings->bool('wallet_points_conversion_enabled')) {
            throw ValidationException::withMessages(['points' => __('wallet_ledger.conversion_disabled')]);
        }
        $min = (int) $this->settings->get('wallet_points_conversion_min');
        if ($points < max(1, $min)) {
            throw ValidationException::withMessages(['points' => __('wallet_ledger.conversion_min', ['min' => $min])]);
        }

        $amount = round($points * $this->settings->pointValue(), 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages(['points' => __('wallet_ledger.conversion_min', ['min' => $min])]);
        }

        return DB::transaction(function () use ($userId, $points, $amount) {
            $loyalty = LoyaltyPoint::where('user_id', $userId)->lockForUpdate()->first();
            if (! $loyalty || (int) $loyalty->points < $points) {
                throw ValidationException::withMessages(['points' => __('wallet_ledger.conversion_insufficient')]);
            }

            $loyalty->points -= $points;
            $loyalty->save();

            $transaction = LoyaltyPointTransaction::create([
                'user_id' => $userId,
                'action' => 'deduct',
                'points' => $points,
                'balance_after' => $loyalty->points,
                'source' => 'wallet_conversion',
                'meta' => ['amount' => $amount, 'point_value' => $this->settings->pointValue()],
            ]);

            // Points are a reward, not paid money: they land in the non-withdrawable bonus bucket.
            $this->ledger->credit($userId, $amount, 'points_conversion', [
                'bucket' => 'bonus',
                'source_type' => 'loyalty_points_transaction',
                'source_id' => $transaction->id,
                'idempotency_key' => 'points_conversion:'.$transaction->id,
                'meta' => ['points' => $points],
            ]);

            return ['points' => $points, 'amount' => $amount, 'points_balance' => (int) $loyalty->points];
        });
    }
}
