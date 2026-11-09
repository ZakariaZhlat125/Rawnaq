<?php
namespace App\Services;

use Modules\Promotion\Models\Coupon;

class BookingCoupon
{
    public static function serviceIds($value): array
    {
        for ($i=0; $i<2 && is_string($value); $i++) {
            $decoded = json_decode($value, true);
            $value = json_last_error() === JSON_ERROR_NONE ? $decoded : explode(',', $value);
        }
        if (!is_array($value)) return [-1];
        foreach ($value as $id) {
            if ((!is_int($id) && !is_string($id)) || !preg_match('/^\d+$/', (string) $id)) return [-1];
        }
        return array_values(array_unique(array_map('intval', $value)));
    }

    /** Prices are authoritative booking amounts before VAT. Products and gifts are never passed here. */
    public static function calculate(array $coupon, array $lines, float $packages = 0, ?string $today = null): array
    {
        $today ??= (new \DateTimeImmutable('now', new \DateTimeZone('Asia/Riyadh')))->format('Y-m-d');
        if (!empty($coupon['is_expired']) || (int) ($coupon['use_limit'] ?? 0) < 1 ||
            empty($coupon['start_date_time']) || empty($coupon['end_date_time']) ||
            $today < substr($coupon['start_date_time'], 0, 10) || $today > substr($coupon['end_date_time'], 0, 10)) {
            return ['error' => 'الكوبون غير صالح أو خارج فترة الصلاحية أو استنفد عدد الاستخدامات.'];
        }
        $base = round(array_sum(array_map(fn ($line) => max(0, (float) $line['amount']), $lines)) + max(0, $packages), 2);
        if ($base <= 0) return ['error' => 'الكوبون متاح للحجوزات فقط، ولا يطبق على المنتجات أو بطاقات الهدايا.'];
        if ($base < (float) ($coupon['minimum_booking_amount'] ?? 0)) return ['error' => 'قيمة الحجز أقل من الحد الأدنى لاستخدام الكوبون: '.number_format($coupon['minimum_booking_amount'], 2).' ر.س قبل الضريبة.'];
        $allowed = self::serviceIds($coupon['services'] ?? []);
        if (!$allowed || in_array(-1, $allowed, true)) return ['error' => 'لم يتم تحديد خدمات صالحة لهذا الكوبون.'];
        if ($allowed && !in_array(0, $allowed, true)) {
            if ($packages > 0 || !$lines) return ['error' => 'هذا الكوبون مخصص لخدمات بعينها ولا يطبق على الباقات.'];
            foreach ($lines as $line) {
                if (!in_array((int) $line['service_id'], $allowed, true)) return ['error' => 'توجد خدمات في الحجز غير مشمولة في تخصيص هذا الكوبون.'];
            }
        }
        $discount = ($coupon['discount_type'] ?? '') === 'percent'
            ? $base * min(100, max(0, (float) ($coupon['discount_percentage'] ?? 0))) / 100
            : (float) ($coupon['discount_amount'] ?? 0);
        return ['discount' => round(min($base, max(0, $discount)), 2), 'booking_base' => $base];
    }

    public function find(string $code): ?Coupon
    {
        return Coupon::where('coupon_code', trim($code))->whereNull('deleted_at')
            ->whereHas('promotion', fn ($q) => $q->where('status', 1))->first();
    }

    public function quote(string $code, array $lines, float $packages = 0): array
    {
        $coupon = $this->find($code);
        return $coupon ? self::calculate($coupon->toArray(), $lines, $packages) : ['error' => 'كود الخصم غير صالح أو متوقف.'];
    }

    public function quoteBooking(string $code, int $bookingId): array
    {
        $booking = \Modules\Booking\Models\Booking::with(['bookingService', 'bookingPackages'])->findOrFail($bookingId);
        $fee = max(0, (float) ($booking->home_service_fee ?? 0));
        $lines = $booking->bookingService->map(function ($service) use (&$fee) {
            $price = max(0, (float) $service->service_price - (float) $service->discount_amount);
            $excluded = min($fee, $price); $fee -= $excluded;
            return ['service_id' => $service->service_id, 'amount' => $price - $excluded];
        })->all();
        return $this->quote($code, $lines, (float) $booking->bookingPackages->sum('package_price'));
    }
}
