<?php
namespace App\Observers;

use App\Services\QuietHoursRewardService;
use Modules\Booking\Models\Booking;

class QuietHoursBookingObserver
{
    public function saving(Booking $booking): void
    {
        app(QuietHoursRewardService::class)->snapshot($booking);
    }

    public function saved(Booking $booking): void
    {
        app(QuietHoursRewardService::class)->award($booking);
    }
}
