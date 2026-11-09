<?php

namespace App\Observers;

use App\Services\BookingWhatsAppService;
use Modules\Booking\Models\Booking;

class BookingWhatsAppObserver
{
    public function created(Booking $booking): void
    {
        // Pending website bookings are shopping-cart items until checkout succeeds.
        if ($booking->status === 'confirmed') {
            app(BookingWhatsAppService::class)->enqueue($booking, 'created');
        }
    }

    public function updated(Booking $booking): void
    {
        if (! $booking->wasChanged('status')) {
            return;
        }
        if ($booking->status === 'confirmed') {
            app(BookingWhatsAppService::class)->enqueue($booking, 'created');
        } elseif ($booking->status === 'cancelled') {
            app(BookingWhatsAppService::class)->enqueue($booking, 'cancelled');
        }
    }

    public function deleted(Booking $booking): void
    {
        if (in_array($booking->status, ['confirmed', 'check_in'], true)) {
            app(BookingWhatsAppService::class)->enqueue($booking, 'cancelled');
        }
    }
}
