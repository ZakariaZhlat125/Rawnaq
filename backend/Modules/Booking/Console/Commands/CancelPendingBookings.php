<?php

namespace Modules\Booking\Console\Commands;

use Illuminate\Console\Command;
use Modules\Booking\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CancelPendingBookings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'booking:cancel-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancel and delete unpaid pending bookings created from the website/cart after 10 minutes';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Checking for pending website bookings to cancel...');

        // Find bookings that:
        // - status is 'pending'
        // - payment_type is 'cart' or 'payment' (bookings created via frontend/cart)
        // - unpaid (using scopeUnpaid or checking transaction payment_status != 1)
        // - created_at is older than 10 minutes ago
        $tenMinutesAgo = Carbon::now()->subMinutes(10);

        $bookings = Booking::where('status', 'pending')
            ->whereIn('payment_type', ['cart', 'payment'])
            ->where('created_at', '<=', $tenMinutesAgo)
            ->unpaid()
            ->get();

        if ($bookings->isEmpty()) {
            $this->info('No bookings found to cancel.');
            return Command::SUCCESS;
        }

        foreach ($bookings as $booking) {
            try {
                // Delete related records
                $booking->bookingService()->delete();
                $booking->packages()->delete();
                $booking->products()->delete();
                $booking->transactions()->delete();
                $booking->userCouponRedeem()->delete();
                
                // Delete the booking itself (standard soft delete)
                $booking->delete();

                Log::info("Auto-cancelled and deleted pending booking #{$booking->id} due to payment timeout (10 minutes).");
                $this->info("Cancelled booking #{$booking->id}");
            } catch (\Exception $e) {
                Log::error("Failed to delete booking #{$booking->id}: " . $e->getMessage());
                $this->error("Error cancelling booking #{$booking->id}");
            }
        }

        $this->info('Finished cancelling pending bookings.');
        return Command::SUCCESS;
    }
}
