<?php

namespace App\Services\Wallet;

use App\Models\PaymentAttempt;
use Illuminate\Support\Facades\DB;
use Modules\Wallet\Models\WalletHistory;
use Modules\Wallet\Models\WalletTopupOffer;

class WalletTopupService
{
    public function __construct(private WalletLedger $ledger) {}

    /**
     * Credit a top-up attempt that the gateway confirmed as paid. Safe to call twice
     * (redirect + retry): the attempt row is locked and the credits are idempotent.
     */
    public function creditPaidAttempt(PaymentAttempt $attempt, array $verification = []): void
    {
        DB::transaction(function () use ($attempt, $verification) {
            $locked = PaymentAttempt::whereKey($attempt->id)->lockForUpdate()->first();
            if (! $locked || $locked->status === PaymentAttempt::STATUS_PAID) {
                return;
            }

            $amount = (float) $locked->amount;
            $this->ledger->credit((int) $locked->user_id, $amount, 'topup', [
                'activity_type' => 'wallet_top_up',
                'source_type' => 'payment_attempt',
                'source_id' => $locked->id,
                'idempotency_key' => 'topup:attempt:'.$locked->id,
                'meta' => ['gateway' => $locked->gateway, 'transaction_id' => $locked->external_id],
            ]);

            $offerId = $locked->meta['offer_id'] ?? null;
            $offer = $offerId ? WalletTopupOffer::where('is_active', true)->find($offerId) : null;
            if ($offer && $amount + 0.0001 >= (float) $offer->min_amount && $this->hasUsesLeft($offer, (int) $locked->user_id)) {
                $this->ledger->credit((int) $locked->user_id, (float) $offer->bonus_amount, 'campaign', [
                    'bucket' => 'bonus',
                    'expires_at' => $offer->bonus_expiry_days ? now()->addDays($offer->bonus_expiry_days) : null,
                    'source_type' => 'wallet_topup_offer',
                    'source_id' => $offer->id,
                    'idempotency_key' => 'campaign:attempt:'.$locked->id,
                    'message' => $offer->title_ar,
                    'meta' => ['payment_attempt_id' => $locked->id],
                ]);
            }

            $locked->update([
                'status' => PaymentAttempt::STATUS_PAID,
                'meta' => array_merge($locked->meta ?? [], ['verify_response' => $verification['raw'] ?? null]),
            ]);
            $attempt->setRawAttributes($locked->getAttributes(), true);
        });
    }

    /** Running offer with the highest threshold the amount reaches and the customer can still use. */
    public function bestOfferFor(int $userId, float $amount): ?WalletTopupOffer
    {
        return WalletTopupOffer::running()
            ->where('min_amount', '<=', $amount)
            ->orderByDesc('min_amount')
            ->orderByDesc('bonus_amount')
            ->get()
            ->first(fn (WalletTopupOffer $offer) => $this->hasUsesLeft($offer, $userId));
    }

    public function runningOffers(int $userId): array
    {
        return WalletTopupOffer::running()
            ->orderBy('min_amount')
            ->get()
            ->filter(fn (WalletTopupOffer $offer) => $this->hasUsesLeft($offer, $userId))
            ->map(fn (WalletTopupOffer $offer) => [
                'id' => $offer->id,
                'title_ar' => $offer->title_ar,
                'title_en' => $offer->title_en,
                'min_amount' => $offer->min_amount,
                'bonus_amount' => $offer->bonus_amount,
                'bonus_expiry_days' => $offer->bonus_expiry_days,
                'ends_at' => $offer->ends_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    private function hasUsesLeft(WalletTopupOffer $offer, int $userId): bool
    {
        if (! $offer->max_uses_per_user) {
            return true;
        }

        $used = WalletHistory::where('user_id', $userId)
            ->where('source', 'campaign')
            ->where('source_type', 'wallet_topup_offer')
            ->where('source_id', $offer->id)
            ->count();

        return $used < $offer->max_uses_per_user;
    }
}
