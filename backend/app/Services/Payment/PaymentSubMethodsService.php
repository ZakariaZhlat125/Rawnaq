<?php

namespace App\Services\Payment;

use Illuminate\Http\Request;
use App\Models\LoyaltyPoint;
use Illuminate\Support\Facades\DB;
use App\Models\LoyaltyPointTransaction;
use App\Services\Wallet\InsufficientWalletBalance;
use App\Services\Wallet\WalletLedger;

class PaymentSubMethodsService
{
    /**
     * Apply wallet and loyalty payments.
     *
     * With $commit the wallet/points are actually deducted. Pass $invoiceId so the
     * deductions are linked to the invoice (used by refunds and to make a repeated
     * commit for the same invoice a no-op).
     */
    public function apply(int $userId, Request $request, float $amount, bool $commit = false, ?int $invoiceId = null): array
    {
        $final = $amount;
        $usedWallet = $usedLoyalty = 0;
        $usedLoyaltyAmount = 0;

        $isWallet = (bool) $request->wallet;
        $isLoyalty = (bool) $request->loyalty;
        $walletAmount = $request->filled('wallet_amount') ? max(0, (float) $request->wallet_amount) : null;
        $loyaltyPoints = $request->filled('loyalty_points') ? max(0, (int) $request->loyalty_points) : null;
        $ledger = app(WalletLedger::class);

        DB::transaction(function () use (&$final, &$usedWallet, &$usedLoyalty, &$usedLoyaltyAmount, $userId, $isWallet, $isLoyalty, $walletAmount, $loyaltyPoints, $commit, $invoiceId, $ledger) {
            if ($isWallet && $final > 0) {
                $walletCap = $walletAmount === null ? $final : $walletAmount;
                $usedWallet = round(min($ledger->spendable($userId), $final, $walletCap), 2);
                if ($usedWallet > 0 && $commit) {
                    $options = [
                        'activity_type' => 'withdraw',
                        'source_type' => $invoiceId ? 'invoice' : null,
                        'source_id' => $invoiceId,
                        'idempotency_key' => $invoiceId ? 'payment:invoice:'.$invoiceId : null,
                    ];
                    try {
                        $ledger->debit($userId, $usedWallet, 'payment', $options);
                    } catch (InsufficientWalletBalance $e) {
                        // Balance moved between the read and the lock: use what is left.
                        $usedWallet = round(min($e->available, $usedWallet), 2);
                        if ($usedWallet > 0) {
                            $ledger->debit($userId, $usedWallet, 'payment', $options);
                        }
                    }
                }
                if ($usedWallet > 0) {
                    $final -= $usedWallet;
                }
            }

            if ($isLoyalty && $final > 0) {
                $rate = \App\Models\Setting::get('point_value') ?? 0.5;
                $rate = max((float) $rate, 0.01);
                $loyalty = LoyaltyPoint::where('user_id', $userId)->lockForUpdate()->first();
                if ($loyalty && $loyalty->points > 0) {
                    $availablePoints = max((int) $loyalty->points, 0);
                    $usablePoints = $loyaltyPoints === null ? $availablePoints : min($loyaltyPoints, $availablePoints);
                    $maxUse = $usablePoints * $rate;
                    $used = min($final, $maxUse);
                    $pointsUsed = min($usablePoints, (int) ceil($used / $rate));
                    $used = min($final, $pointsUsed * $rate);
                    if ($pointsUsed <= 0 || $used <= 0) {
                        return;
                    }
                    if ($commit) {
                        $loyalty->points -= $pointsUsed;
                        $loyalty->save();
                        LoyaltyPointTransaction::create([
                            'user_id' => $userId,
                            'action' => 'deduct',
                            'points' => $pointsUsed,
                            'balance_after' => $loyalty->points,
                            'source' => 'Loyalty points payment deduction',
                            'source_id' => $invoiceId,
                            'meta' => $invoiceId ? ['invoice_id' => $invoiceId, 'amount' => round($used, 2)] : null,
                        ]);
                    }
                    $usedLoyalty = $pointsUsed;
                    $usedLoyaltyAmount = $used;
                    $final -= $used;
                }
            }
        });

        return [
            'remaining_amount' => max($final, 0),
            'used_wallet' => $usedWallet,
            'used_loyalty' => $usedLoyalty,
            'used_loyalty_amount' => $usedLoyaltyAmount,
            'used_gift' => 0,
        ];
    }
}
