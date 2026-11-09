<?php

namespace Tests\Unit;

use App\Services\Payment\BookingPaymentEarningsCalculator;
use PHPUnit\Framework\TestCase;

class BookingPaymentEarningsCalculatorTest extends TestCase
{
    public function test_it_calculates_fixed_and_percentage_commissions(): void
    {
        $calculator = new BookingPaymentEarningsCalculator();

        $amount = $calculator->calculate(200, [
            ['type' => 'fixed', 'value' => 15],
            ['type' => 'percentage', 'value' => 10],
        ]);

        $this->assertSame(35.0, $amount);
    }

    public function test_it_returns_zero_for_empty_rules(): void
    {
        $calculator = new BookingPaymentEarningsCalculator();

        $this->assertSame(0.0, $calculator->calculate(200, []));
    }
}
