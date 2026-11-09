<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\WaitingBookingSyncService;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingService;
use PHPUnit\Framework\TestCase;

class WaitingBookingSyncServiceTest extends TestCase
{
    public function test_it_builds_waiting_device_payload_from_booking_data(): void
    {
        $user = new User();
        $user->first_name = 'Sami';
        $user->last_name = 'Care';
        $user->mobile = '0500000000';

        $booking = new Booking();
        $booking->branch_id = 7;
        $booking->start_date_time = '2026-03-26 14:30:00';
        $booking->setRelation('user', $user);

        $bookingService = new BookingService();
        $bookingService->service_id = 12;
        $bookingService->employee_id = 33;
        $bookingService->start_date_time = '2026-03-26 15:45:00';

        $payload = (new WaitingBookingSyncService())->buildPayload($booking, $bookingService);

        $this->assertSame([
            'company_id' => 5,
            'branch_id' => 7,
            'service_id' => 12,
            'employee_id' => 33,
            'date' => '2026-03-26',
            'start_time' => '15:45',
            'name' => 'Rawnaq',
            'phone' => '0500000000',
        ], $payload);
    }
}
