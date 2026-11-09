<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use Modules\Promotion\Models\Coupon;
use Modules\Promotion\Models\UserCouponRedeem;

class InvoicePaymentRecorderService
{
    public function create(
        int $userId,
        float $discountAmount,
        float $tax,
        float $finalTotal,
        array $bookingIds,
        array $giftIds,
        array $orderGroupIds,
        string $couponCode,
        string $paymentMethod
    ): int {
        $invoice = Invoice::create([
            'user_id' => $userId,
            'cart_ids' => $bookingIds,
            'gift_ids' => $giftIds,
            'product_ids' => $orderGroupIds,
            'discount_amount' => $discountAmount,
            'taxs_service' => $tax,
            'loyalty_points_discount' => 0,
            'final_total' => $finalTotal,
            'payment_method' => $paymentMethod,
        ]);

        $this->recordCouponRedeem($userId, $couponCode, $discountAmount, $invoice->id, $bookingIds);

        return (int) $invoice->id;
    }

    private function recordCouponRedeem(int $userId, string $couponCode, float $discountAmount, int $invoiceId, array $bookingIds): void
    {
        if ($couponCode === '' || $discountAmount <= 0) {
            return;
        }

        if (!$bookingIds) throw new \RuntimeException('الكوبون متاح للحجوزات فقط.');
        if (UserCouponRedeem::where('invoice_id', $invoiceId)->exists()) return;
        $coupon = Coupon::where('coupon_code', $couponCode)->lockForUpdate()->first();
        if (!$coupon || $coupon->use_limit < 1) {
            throw new \RuntimeException('استنفد الكوبون عدد الاستخدامات المتاح.');
        }

        $coupon->decrement('use_limit');

        UserCouponRedeem::create([
            'user_id' => $userId,
            'coupon_code' => $coupon->coupon_code,
            'discount' => $discountAmount,
            'coupon_id' => $coupon->id,
            'invoice_id' => $invoiceId,
            'booking_id' => count($bookingIds) === 1 ? $bookingIds[0] : null,
        ]);
    }
}
