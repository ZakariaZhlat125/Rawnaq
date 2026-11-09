<?php

namespace App\Services\Payment;

use App\Services\AffiliateCommissionService;
use App\Services\WaitingBookingSyncService;
use Illuminate\Support\Facades\DB;

class PaymentFinalizerService
{
    public function finalizePayment( int $userId, float $paidAmount, float $tax, float $discountAmount, string $pageType, array $cartIds, array $giftIds, string $paymentMethod, string $couponCode, bool $submethodsApplied = false ): int {
        $invoiceId = 0;

        DB::transaction(function () use ($userId , $paidAmount , $tax , $discountAmount , $pageType , $cartIds , $giftIds , $paymentMethod , $couponCode , &$invoiceId) {
            $orderGroupIds = [];

            if ($pageType === CheckoutType::CART) {
                $orderData = app(CartOrderConversionService::class)->convertUserCartToPaidOrders($userId);

                if (isset($orderData['error'])) {
                    throw new \RuntimeException($orderData['error']);
                }

                $orderGroupIds = $orderData['order_group_ids'] ?? [];
            }

            app(LoyaltyPointAwardService::class)->award($userId, $paidAmount);
            
            $invoiceId = app(InvoicePaymentRecorderService::class)->create($userId, $discountAmount,$tax,$paidAmount,$cartIds,$giftIds,$orderGroupIds,$couponCode,$paymentMethod);
            
            app(AffiliateCommissionService::class)->handleSuccessfulPurchase($userId, $invoiceId, $paidAmount);
            app(BookingTransactionRecorderService::class)->markBookingsPaid($cartIds,'INV-' . $invoiceId, $paymentMethod ?: 'sub_methods');
            app(BookingPaymentEarningsService::class)->syncForPaidBookings($cartIds);
            app(GiftCardPaymentActivatorService::class)->activatePurchasedGiftCards($userId, $giftIds);
        });
        app(WaitingBookingSyncService::class)->syncPaidBookings($cartIds);
        app(PaymentSuccessSmsService::class)->sendForInvoice($invoiceId);

        return $invoiceId;
    }
}
