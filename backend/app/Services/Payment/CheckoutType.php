<?php

namespace App\Services\Payment;

class CheckoutType
{
    public const CART = 'cart';
    public const PAYMENT = 'payment';

    public static function values(): array
    {
        return [
            self::CART,
            self::PAYMENT,
        ];
    }

    public static function fromLegacyValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? self::PAYMENT : self::CART;
        }

        if (is_numeric($value)) {
            return ((int) $value) === 1 ? self::PAYMENT : self::CART;
        }

        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            self::PAYMENT, '1', 'true' => self::PAYMENT,
            default => self::CART,
        };
    }
}
