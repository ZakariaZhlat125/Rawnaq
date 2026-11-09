<?php

namespace Tests\Unit;

use App\Services\Payment\CheckoutType;
use App\Services\Payment\CheckoutTypeResolver;
use PHPUnit\Framework\TestCase;

class CheckoutTypeResolverTest extends TestCase
{
    public function test_it_resolves_page_type_directly(): void
    {
        $resolver = new CheckoutTypeResolver();

        $this->assertSame(CheckoutType::PAYMENT, $resolver->resolve([
            'page_type' => 'payment',
        ]));

        $this->assertSame(CheckoutType::CART, $resolver->resolve([
            'page_type' => 'cart',
        ]));
    }

    public function test_it_resolves_legacy_is_buy_now_values(): void
    {
        $resolver = new CheckoutTypeResolver();

        $this->assertSame(CheckoutType::PAYMENT, $resolver->resolve([
            'isBuyNow' => true,
        ]));

        $this->assertSame(CheckoutType::CART, $resolver->resolve([
            'isBuyNow' => false,
        ]));
    }
}
