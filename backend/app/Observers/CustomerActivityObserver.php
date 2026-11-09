<?php

namespace App\Observers;

use App\Models\LoyaltyPointTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingService;
use Modules\Booking\Models\BookingTransaction;
use Modules\Product\Models\Order;
use Modules\Wallet\Models\WalletHistory;

/** Store the inbox entry on the same connection/transaction as the activity. */
class CustomerActivityObserver
{
    public function created(Model $model): void
    {
        $this->record($model, true);
    }

    public function deleted(Model $model): void
    {
        if ($model instanceof Booking || $model instanceof BookingService || $model instanceof Order) {
            $this->record($model, false, true);
        }
    }

    public function updated(Model $model): void
    {
        if ($model instanceof WalletHistory || $model instanceof LoyaltyPointTransaction) {
            return;
        }
        if ($model->wasChanged(['status', 'payment_status', 'delivery_status', 'start_date_time', 'branch_id', 'employee_id', 'service_id', 'service_price', 'duration_min', 'total_amount', 'discount_amount', 'coupon_code'])) {
            $this->record($model, false);
        }
    }

    private function record(Model $model, bool $created, bool $deleted = false): void
    {
        $owner = $model;
        $group = 'booking';
        $details = [];
        if ($model instanceof BookingService || $model instanceof BookingTransaction) {
            $owner = Booking::on($model->getConnectionName())->find($model->booking_id);
        }
        if (! $owner?->user_id) {
            return;
        }
        $id = $owner->getKey();
        if ($model instanceof WalletHistory) {
            $group = 'wallet';
            $activity = is_array($model->activity_data) ? $model->activity_data : json_decode($model->activity_data ?? '{}', true);
            $amount = abs((float) ($activity['credit_debit_amount'] ?? $activity['reward_value'] ?? $activity['amount'] ?? 0));
            $debit = $model->direction
                ? $model->direction === 'debit'
                : in_array($model->activity_type, ['withdraw', 'withdrawal', 'debit']);
            $title = $debit ? 'خصم من المحفظة' : 'إضافة إلى المحفظة';
            $message = $title.' بقيمة '.number_format($amount, 2).' ر.س';
            $details = ['amount' => $amount, 'activity_type' => $model->activity_type];
        } elseif ($model instanceof LoyaltyPointTransaction) {
            if ((int) $model->points === 0) {
                return;
            }
            $group = 'loyalty';
            $title = match ($model->action) {
                'add' => 'إضافة نقاط ولاء',
                'expire', 'expired' => 'انتهاء صلاحية نقاط ولاء',
                default => 'خصم نقاط ولاء',
            };
            if (($model->meta['reason'] ?? null) === 'Points expired') {
                $title = 'انتهاء صلاحية نقاط ولاء';
            }
            $message = $title.': '.abs((int) $model->points).' نقطة. الرصيد الحالي: '.$model->balance_after.' نقطة';
            $details = ['points' => $model->points, 'balance_after' => $model->balance_after, 'action' => $model->action];
        } else {
            $group = $model instanceof Order ? 'shop' : 'booking';
            $label = $group === 'shop' ? 'الطلب' : 'الحجز';
            $title = ($deleted ? 'حذف ' : ($created ? 'تسجيل ' : 'تحديث ')).$label;
            if ($model instanceof BookingTransaction) {
                $title = 'تحديث دفع الحجز';
            } elseif ($model instanceof BookingService) {
                $title = $deleted ? 'حذف خدمة من الحجز' : ($created ? 'إضافة خدمة للحجز' : 'تحديث خدمة الحجز');
            }
            $fields = $created ? $model->getAttributes() : $model->getChanges();
            $labels = ['status' => 'الحالة', 'delivery_status' => 'حالة الطلب', 'payment_status' => 'حالة الدفع', 'start_date_time' => 'الموعد', 'branch_id' => 'الفرع', 'employee_id' => 'الموظف', 'service_id' => 'الخدمة', 'service_price' => 'سعر الخدمة', 'duration_min' => 'مدة الخدمة', 'total_amount' => 'الإجمالي', 'discount_amount' => 'الخصم', 'coupon_code' => 'كود الخصم'];
            $states = ['pending' => 'قيد الانتظار', 'confirmed' => 'مؤكد', 'completed' => 'مكتمل', 'cancelled' => 'ملغي', 'canceled' => 'ملغي', 'check_in' => 'تم الحضور', 'checkout' => 'تم الانتهاء', 'check_out' => 'تم الانتهاء', 'processing' => 'قيد التجهيز', 'shipped' => 'تم الشحن', 'delivered' => 'تم التسليم', 'paid' => 'مدفوع', 'unpaid' => 'غير مدفوع'];
            $message = $title.' #'.$id;
            foreach ($labels as $field => $label) {
                if (array_key_exists($field, $fields)) {
                    $value = $fields[$field];
                    // Dates may arrive as Carbon objects; they can't be array keys.
                    if ($value instanceof \DateTimeInterface) {
                        $value = $value->format('Y-m-d H:i:s');
                    }
                    $display = $field === 'payment_status' && in_array((string) $value, ['0', '1'], true) ? ((int) $value === 1 ? 'مدفوع' : 'غير مدفوع') : (is_scalar($value) ? ($states[$value] ?? $value) : $value);
                    $message .= ' — '.$label.': '.$display;
                    $details[$field] = $value;
                }
            }
        }
        // No external delivery inside a financial transaction. Rolled-back entries
        // are rolled back with their activity and cannot reach the live feed.
        $user = User::on($model->getConnectionName())->find($owner->user_id);
        $user?->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => self::class,
            'data' => ['subject' => $title, 'data' => array_merge($details, [
                'type' => $title, 'message' => $message, 'notification_group' => $group,
                'id' => $id, 'event' => $group.($deleted ? '.deleted' : ($created ? '.created' : '.updated')),
            ])],
        ]);
    }
}
