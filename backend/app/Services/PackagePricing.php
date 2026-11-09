<?php

namespace App\Services;

use InvalidArgumentException;

class PackagePricing
{
    /** Compare prices in halalas, including service quantities. */
    public function calculate(array $rows, array $catalog): array
    {
        if (!$rows) throw new InvalidArgumentException('services_required');
        $original = $price = 0;
        $services = $seen = [];
        foreach ($rows as $row) {
            $id = $row['service_id'] ?? null;
            $qty = filter_var($row['qty'] ?? null, FILTER_VALIDATE_INT);
            $total = $row['totalPrice'] ?? null;
            if (!$id || isset($seen[$id]) || !isset($catalog[$id]) || !$qty || $qty < 1
                || !is_numeric($total) || !is_finite((float) $total) || (float) $total <= 0) {
                throw new InvalidArgumentException('invalid_services');
            }
            $seen[$id] = true;
            $unit = (int) round((float) $catalog[$id]['price'] * 100);
            $line = (int) round((float) $total * 100);
            if ($line <= 0 || $line > $unit * $qty) throw new InvalidArgumentException('invalid_discount');
            $original += $unit * $qty;
            $price += $line;
            $services[] = [
                'service_id' => (int) $id,
                'service_name' => $catalog[$id]['name'],
                'service_price' => $unit / 100,
                'qty' => $qty,
                'discounted_price' => $line / (100 * $qty),
                'totalPrice' => $line / 100,
            ];
        }
        if ($price >= $original) throw new InvalidArgumentException('price_must_be_lower');
        return ['services' => $services, 'package_price' => $price / 100,
            'services_total_price' => $original / 100, 'savings_amount' => ($original - $price) / 100];
    }
}
