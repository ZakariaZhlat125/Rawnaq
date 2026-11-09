<?php

namespace App\Services\Payment;

class CheckoutTypeResolver
{
    public function resolve(array $input): string
    {
        if (array_key_exists('page_type', $input)) {
            return CheckoutType::fromLegacyValue($input['page_type']);
        }

        if (array_key_exists('isBuyNow', $input)) {
            return CheckoutType::fromLegacyValue($input['isBuyNow']);
        }

        return CheckoutType::CART;
    }

    public function isBuyNow(string $pageType): bool
    {
        return $pageType === CheckoutType::PAYMENT;
    }
}
