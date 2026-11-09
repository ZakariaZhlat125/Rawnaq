<?php

namespace App\Services;

use App\Models\LoyaltyPointTransaction;
use Carbon\Carbon;
use Modules\Wallet\Models\WalletHistory;

class WheelCooldownService
{
    public function getLastSpinAt(?int $userId = null, ?string $phone = null, ?string $guestToken = null): ?Carbon
    {
        $timestamps = [];

        $loyaltyQuery = LoyaltyPointTransaction::query()->where('source', 'wheel');

        if ($userId || $phone || $guestToken) {
            $loyaltyQuery->where(function ($query) use ($userId, $phone, $guestToken) {
                if ($userId) {
                    $query->orWhere('user_id', $userId);
                }

                if ($phone) {
                    $query->orWhere('meta->mobile', $phone)->orWhere('meta->guest_phone', $phone);
                }

                if ($guestToken) {
                    $query->orWhere('meta->guest_token', $guestToken);
                }
            });
        }

        $lastLoyaltySpinAt = $loyaltyQuery->latest('created_at')->value('created_at');
        if ($lastLoyaltySpinAt) {
            $timestamps[] = Carbon::parse($lastLoyaltySpinAt);
        }

        if ($userId) {
            $walletSpin = WalletHistory::query()
                ->where('user_id', $userId)
                ->where('activity_type', 'wheel_win')
                ->orderByDesc('datetime')
                ->orderByDesc('created_at')
                ->first(['datetime', 'created_at']);

            if ($walletSpin) {
                $walletSpinAt = $walletSpin->datetime ?: $walletSpin->created_at;
                if ($walletSpinAt) {
                    $timestamps[] = Carbon::parse($walletSpinAt);
                }
            }
        }

        if (empty($timestamps)) {
            return null;
        }

        return collect($timestamps)->sortDesc()->first();
    }

    public function shouldShowWheel(int $intervalDays, ?int $userId = null, ?string $phone = null, ?string $guestToken = null): bool
    {
        $lastSpinAt = $this->getLastSpinAt($userId, $phone, $guestToken);

        if (! $lastSpinAt) {
            return true;
        }

        return Carbon::now()->greaterThanOrEqualTo($lastSpinAt->copy()->addDays($intervalDays));
    }
}
