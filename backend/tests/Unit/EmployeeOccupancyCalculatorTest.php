<?php

namespace Tests\Unit;

use App\Services\EmployeeOccupancyCalculator;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class EmployeeOccupancyCalculatorTest extends TestCase
{
    public function test_overlapping_bookings_and_shifts_are_counted_once(): void
    {
        $result = (new EmployeeOccupancyCalculator())->calculate([[0, 3600], [1800, 7200]], [[0, 1800], [900, 3600]], 0, 7200);
        $this->assertSame(120.0, $result['available_minutes']);
        $this->assertSame(60.0, $result['booked_minutes']);
        $this->assertSame(50.0, $result['percentage']);
    }

    public function test_zero_capacity_is_unavailable_but_empty_bookings_are_zero_percent(): void
    {
        $calculator = new EmployeeOccupancyCalculator();
        $this->assertNull($calculator->calculate([], [[0, 100]], 0, 100)['percentage']);
        $this->assertSame(0.0, $calculator->calculate([[0, 3600]], [], 0, 3600)['percentage']);
    }

    public function test_bookings_are_clipped_to_the_period_and_working_hours(): void
    {
        $result = (new EmployeeOccupancyCalculator())->calculate([[0, 7200]], [[-900, 1800], [5400, 9000]], 900, 6300);
        $this->assertSame(90.0, $result['available_minutes']);
        $this->assertSame(30.0, $result['booked_minutes']);
        $this->assertSame(33.3, $result['percentage']);
    }

    public function test_overnight_shift_and_overlapping_breaks(): void
    {
        $calculator = new EmployeeOccupancyCalculator();
        $ranges = $calculator->workingRanges('2026-09-28', [
            'start_time' => '22:00', 'end_time' => '06:00',
            'breaks' => [['start_break' => '01:00', 'end_break' => '02:00'], ['start' => '01:30', 'end' => '02:30']],
        ], 'Asia/Riyadh');
        $start = CarbonImmutable::parse('2026-09-28', 'Asia/Riyadh')->timestamp;
        $this->assertSame(390.0, $calculator->calculate($ranges, [], $start, $start + 172800)['available_minutes']);
        $this->assertSame(120.0, $calculator->calculate($ranges, [], $start, $start + 86400)['available_minutes']);
    }

    public function test_leave_and_holiday_intervals_remove_capacity(): void
    {
        $calculator = new EmployeeOccupancyCalculator();
        $ranges = $calculator->subtract([[0, 7200]], [[0, 1800], [900, 2700], [6300, 9000]]);
        $result = $calculator->calculate($ranges, [[0, 10000]], 0, 10000);
        $this->assertSame(60.0, $result['available_minutes']);
        $this->assertSame(100.0, $result['percentage']);
    }

    public function test_holiday_missing_and_zero_length_schedules_do_not_invent_capacity(): void
    {
        $calculator = new EmployeeOccupancyCalculator();
        $this->assertSame([], $calculator->workingRanges('2026-09-28', [], 'Asia/Riyadh'));
        $this->assertSame([], $calculator->workingRanges('2026-09-28', ['start_time' => '09:00', 'end_time' => '09:00'], 'Asia/Riyadh'));
        $this->assertSame([], $calculator->workingRanges('2026-09-28', ['start_time' => '09:00', 'end_time' => '17:00', 'is_holiday' => 1], 'Asia/Riyadh'));
    }
}
