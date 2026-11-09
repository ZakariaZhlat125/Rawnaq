<?php

namespace App\Services;

use App\Models\User;
use Modules\Affiliate\Models\Affiliate;
use Modules\Tracking\Models\Visitor;
use Modules\Tracking\Models\Conversion;

class AffiliateCommissionService
{
    public function handleSuccessfulPurchase(int $buyerUserId, int $orderId, float $amount): ?Conversion
    {
        if ($amount <= 0) {
            return null;
        }

        $buyer = User::find($buyerUserId);
        if (! $buyer) {
            return null;
        }

        $affiliate = $this->resolveAffiliateForBuyer($buyer);
        if (! $affiliate || $affiliate->status !== 'active') {
            return null;
        }

        // Prevent self-referral commission
        if ((int) $affiliate->user_id === (int) $buyer->id) {
            return null;
        }

        // Prevent duplicate conversion for same order.
        if (Conversion::where('affiliate_id', $affiliate->id)->where('order_id', $orderId)->exists()) {
            return null;
        }

        $commissionType = $this->commissionType();
        $commissionValue = $this->commissionValue();
        $applyType = $this->commissionApplyType();

        if ( $applyType === 'first_purchase' && Conversion::where('affiliate_id', $affiliate->id)->where('referred_user_id', $buyer->id)->exists()) {
            return null;
        }

        $commissionAmount = $this->calculateCommission($amount, $commissionType, $commissionValue);
        if ($commissionAmount <= 0) {
            return null;
        }

        $conversion = Conversion::create([
            'affiliate_id' => $affiliate->id,
            'referred_user_id' => $buyer->id,
            'visitor_id' => $this->resolveVisitorId($affiliate->id),
            'order_id' => $orderId,
            'amount' => round($amount, 2),
            'commission' => $commissionAmount,
            'commission_type' => $commissionType,
            'commission_value' => $commissionValue,
            'commission_apply_type' => $applyType,
            'status' => 'approved',
        ]);

        $affiliate->increment('wallet_total', $commissionAmount);
        $affiliate->increment('wallet_available', $commissionAmount);

        return $conversion;
    }

    private function resolveAffiliateForBuyer(User $buyer): ?Affiliate
    {
        if ($buyer->referred_by_affiliate_id) {
            return Affiliate::find($buyer->referred_by_affiliate_id);
        }

        $affiliate = null;

        $affiliateIdFromSession = session('affiliate_id');
        if ($affiliateIdFromSession) {
            $affiliate = Affiliate::find($affiliateIdFromSession);
        }

        if (! $affiliate) {
            $token = session('affiliate_token') ?: request()?->cookie('affiliate_token');
            if ($token) {
                $visitor = Visitor::where('token', $token)->latest()->first();
                $affiliate = $visitor?->affiliate;
            }
        }

        if ($affiliate && ! $buyer->referred_by_affiliate_id) {
            $buyer->referred_by_affiliate_id = $affiliate->id;
            $buyer->save();
        }

        return $affiliate;
    }

    private function resolveVisitorId(int $affiliateId): ?int
    {
        $token = session('affiliate_token') ?: request()?->cookie('affiliate_token');

        if (! $token) {
            return null;
        }

        return Visitor::where('token', $token)
            ->where('affiliate_id', $affiliateId)
            ->latest()
            ->value('id');
    }

    private function commissionType(): string
    {
        $type = (string) setting('affiliate_commission_calc_type', 'percentage');
        return in_array($type, ['fixed', 'percentage'], true) ? $type : 'percentage';
    }

    private function commissionValue(): float
    {
        $value = (float) setting('affiliate_commission_value', 3);
        return max(0, round($value, 2));
    }

    private function commissionApplyType(): string
    {
        $type = (string) setting('affiliate_commission_apply_type', 'recurring');
        return in_array($type, ['first_purchase', 'recurring'], true) ? $type : 'recurring';
    }

    private function calculateCommission(float $amount, string $type, float $value): float
    {
        if ($type === 'fixed') {
            return round($value, 2);
        }

        return round(($amount * $value) / 100, 2);
    }
}
