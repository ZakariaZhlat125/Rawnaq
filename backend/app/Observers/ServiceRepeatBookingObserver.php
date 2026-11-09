<?php
namespace App\Observers;
use App\Services\ServiceRepeatRewards;
use Modules\Booking\Models\Booking;
class ServiceRepeatBookingObserver {
    public function saving(Booking $booking): void { app(ServiceRepeatRewards::class)->protect($booking); }
    public function saved(Booking $booking): void { app(ServiceRepeatRewards::class)->onSaved($booking); }
    public function deleted(Booking $booking): void { app(ServiceRepeatRewards::class)->release([$booking->id]); }
}
