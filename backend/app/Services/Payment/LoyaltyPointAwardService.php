<?php

namespace App\Services\Payment;

use App\Models\LoyaltyPoint;
use App\Models\LoyaltyPointTransaction;
use App\Models\Setting;

class LoyaltyPointAwardService
{
    public function award(int $userId, float $paidAmount): void
    {
        $pointsPer100 = Setting::get('points_per_100') ?? 5;
        $pointsToAdd = (int) floor($paidAmount / 100) * (int) $pointsPer100;

        if ($pointsToAdd <= 0) {
            return;
        }

        $loyalty = LoyaltyPoint::firstOrNew(['user_id' => $userId]);
        $loyalty->points = (int) ($loyalty->points ?? 0) + $pointsToAdd;
        $loyalty->save();

        LoyaltyPointTransaction::create([
            'user_id' => $userId,
            'action' => 'add',
            'points' => $pointsToAdd,
            'balance_after' => $loyalty->points,
            'source' => 'إضافة نقاط ولا بنائها على المبلغ الإجمالي:' . $paidAmount,
        ]);
    }
}
