<?php

namespace App\Services\Payment;

class BookingPaymentEarningsCalculator
{
    public function calculate(float $bookingAmount, array $rules): float
    {
        if ($bookingAmount <= 0 || empty($rules)) {
            return 0.0;
        }

        $commission = 0.0;

        foreach ($rules as $rule) {
            $type = (string) ($rule['type'] ?? '');
            $value = (float) ($rule['value'] ?? 0);

            if ($value <= 0) {
                continue;
            }

            if ($type === 'fixed') {
                $commission += $value;
                continue;
            }

            $commission += ($bookingAmount * $value) / 100;
        }

        return round($commission, 2);
    }
}
