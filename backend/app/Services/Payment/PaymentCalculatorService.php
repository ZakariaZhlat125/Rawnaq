<?php

namespace App\Services\Payment;

class PaymentCalculatorService
{
    public function calculateTotal(string $pageType, ?string $couponCode = null): array
    {
        return app(PendingCheckoutService::class)->getPendingCheckoutForCurrentUser($pageType, $couponCode);
    }
}
