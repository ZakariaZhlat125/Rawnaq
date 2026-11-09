<?php

namespace App\Observers;

use App\Services\Wallet\WalletRefundPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Modules\Booking\Models\Booking;
use Modules\Product\Models\Order;

/**
 * Refund to the wallet when a paid booking or product order becomes cancelled.
 * Mass updates (query builder ->update()) do not fire model events and are not refunded.
 */
class WalletRefundObserver
{
    private const CANCELLED = ['cancelled', 'canceled'];

    public function __construct(private WalletRefundPolicy $policy) {}

    public function updated(Model $model): void
    {
        $field = $model instanceof Order ? 'delivery_status' : 'status';
        if (! $model->wasChanged($field)
            || ! in_array($model->{$field}, self::CANCELLED, true)
            || in_array($model->getOriginal($field), self::CANCELLED, true)) {
            return;
        }

        try {
            if ($model instanceof Booking) {
                $this->policy->refundBooking($model);
                // A booking paid with a service gift: give the gift back once all its bookings are cancelled.
                app(\App\Services\Gifts\GiftCardService::class)->onBookingCancelled($model);
            } elseif ($model instanceof Order) {
                $this->policy->refundOrder($model);
            }
        } catch (\Throwable $e) {
            // A failed refund must not block the cancellation; it is logged for manual follow-up
            // and can be retried safely because refunds are idempotent.
            Log::error('Wallet refund failed', [
                'model' => $model::class,
                'id' => $model->getKey(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
