<?php

namespace App\Observers;

use App\Services\MembershipService;
use Modules\Booking\Models\Booking;

/**
 * Automatically awards membership points when a booking is completed.
 *
 * Points calculation: based on the setting "points_per_100" (how many points per 100 SAR spent).
 * Default: 5 points per 100 SAR.
 */
class MembershipPointsObserver
{
    public function __construct(protected MembershipService $membershipService) {}

    /**
     * Handle the Booking "updated" event.
     * Fire only when status changes to "completed".
     */
    public function updated(Booking $booking): void
    {
        // Only award points when booking transitions to "completed"
        if ($booking->status !== 'completed') return;
        if ($booking->getOriginal('status') === 'completed') return;

        $user = $booking->user;
        if (!$user) return;

        $pointsPerHundred = (int) (setting('points_per_100') ?? 5);
        $amount           = (float) ($booking->total_price ?? 0);

        if ($amount <= 0 || $pointsPerHundred <= 0) return;

        // Award points: e.g. 5 points per 100 SAR
        $points = (int) floor(($amount / 100) * $pointsPerHundred);
        if ($points <= 0) return;

        $result = $this->membershipService->addPoints($user, $points);

        \Log::info('Membership: Points awarded on booking completion', [
            'booking_id'  => $booking->id,
            'user_id'     => $user->id,
            'amount'      => $amount,
            'points'      => $points,
            'upgraded'    => $result['upgraded'],
            'new_tier'    => $result['upgraded'] ? $result['new_tier']->name_en : null,
        ]);
    }
}
