<?php
namespace Tests\Unit;

use App\Http\Controllers\HomeBookingController;
use PHPUnit\Framework\TestCase;

class BookingAvailabilityTest extends TestCase
{
    public function test_free_runs_match_minute_by_minute_validation_including_midnight(): void
    {
        $controller = new HomeBookingController();
        foreach ([0, 480, 1380] as $start) {
            $times = [];
            for ($i = 0; $i < 180; $i++) {
                if (($i >= 45 && $i < 60) || ($i >= 110 && $i < 125)) continue;
                $minute = ($start + $i) % 1440;
                $times[] = sprintf('%02d:%02d', intdiv($minute, 60), $minute % 60);
            }
            foreach ([1, 15, 30, 60, 180] as $duration) {
                $expected = [];
                foreach ($times as $time) {
                    [$h, $m] = array_map('intval', explode(':', $time));
                    for ($offset = 0; $offset < $duration; $offset++) {
                        $minute = ($h * 60 + $m + $offset) % 1440;
                        if (!in_array(sprintf('%02d:%02d', intdiv($minute, 60), $minute % 60), $times, true)) continue 2;
                    }
                    $expected[] = $time;
                }
                $this->assertSame($expected, $controller->filterAvailableTimes($times, $duration));
            }
        }
    }
}
