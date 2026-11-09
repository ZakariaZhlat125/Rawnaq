<?php

namespace App\Services;

use App\Models\PaymentAttempt;
use App\Models\Invoice;
use Modules\Booking\Models\Booking;

class EmployeePerformanceReport
{
    public static function allocate(float $collected, float $employeeValue, float $checkoutBase): float
    {
        if ($checkoutBase <= 0 || $employeeValue <= 0) return 0;
        return round(max(0, $collected) * min(1, $employeeValue / $checkoutBase), 2);
    }

    public function build(int $employeeId, string $from, string $to): array
    {
        $bookings = Booking::whereNull('deleted_by')->whereBetween('start_date_time', [$from.' 00:00:00', $to.' 23:59:59'])
            ->where(fn ($q) => $q->whereHas('bookingService', fn ($s) => $s->where('employee_id', $employeeId))
                ->orWhereHas('bookingPackages', fn ($p) => $p->where('employee_id', $employeeId)))
            ->with(['bookingService', 'bookingPackages', 'user', 'transactions'])->orderByDesc('start_date_time')->get();
        $rows = $bookings->mapWithKeys(function ($booking) use ($employeeId) {
            $services = $booking->bookingService->where('employee_id', $employeeId);
            $packages = $booking->bookingPackages->where('employee_id', $employeeId);
            $value = $services->sum(fn ($s) => max(0, (float) $s->service_price - (float) $s->discount_amount)) + $packages->sum('package_price');
            return [$booking->id => ['booking' => $booking, 'services' => $services->count(), 'minutes' => $services->sum('duration_min'), 'value' => $value, 'paid' => $booking->transactions->contains(fn ($t) => (int) $t->payment_status === 1), 'collected' => 0, 'has_collection_record' => false]];
        })->all();

        if ($rows) {
            $invoices = Invoice::whereNotNull('collection_details')->where(function ($q) use ($rows) {
                foreach (array_keys($rows) as $id) $q->orWhereJsonContains('cart_ids', (int) $id)->orWhereJsonContains('cart_ids', (string) $id);
            })->get();
            foreach ($invoices as $invoice) {
                $details = $invoice->collection_details;
                $collected = (float) ($details['gateway'] ?? 0) + (float) ($details['wallet'] ?? 0);
                $base = max(0, (float) $invoice->final_total - (float) $invoice->taxs_service + (float) $invoice->discount_amount);
                foreach ($invoice->cart_ids ?? [] as $id) {
                    if (!isset($rows[$id])) continue;
                    $rows[$id]['has_collection_record'] = true;
                    $rows[$id]['collected'] += self::allocate($collected, $rows[$id]['value'], $base);
                }
            }
            $recordedInvoiceIds = $invoices->pluck('id')->all();
            // Invoice IDs prevent repeated gateway callbacks from counting the same payment twice.
            $attempts = PaymentAttempt::where('status', PaymentAttempt::STATUS_PAID)
                ->where(function ($q) use ($rows) { foreach (array_keys($rows) as $id) $q->orWhereJsonContains('cart_ids', (int) $id)->orWhereJsonContains('cart_ids', (string) $id); })
                ->orderByDesc('id')->get()->unique(fn ($a) => $a->invoice_id ? 'invoice-'.$a->invoice_id : 'attempt-'.$a->id);
            foreach ($attempts as $attempt) {
                if (in_array($attempt->invoice_id, $recordedInvoiceIds)) continue;
                $collected = $attempt->gateway === 'cod' ? (float) ($attempt->meta['deposit_amount'] ?? 0) : (float) $attempt->amount;
                $base = max(0, (float) $attempt->gross_amount - (float) $attempt->tax_amount + (float) $attempt->discount_amount);
                foreach ($attempt->cart_ids ?? [] as $id) {
                    if (!isset($rows[$id])) continue;
                    $rows[$id]['has_collection_record'] = true;
                    $rows[$id]['collected'] += self::allocate($collected, $rows[$id]['value'], $base);
                }
            }
        }
        $values = collect($rows);
        return ['rows' => $values, 'totals' => [
            'bookings' => $values->count(),
            'completed' => $values->filter(fn ($r) => $r['booking']->status === 'completed')->count(),
            'cancelled' => $values->filter(fn ($r) => in_array($r['booking']->status, ['cancelled', 'canceled']))->count(),
            'services' => $values->sum('services'), 'minutes' => $values->where('booking.status', 'completed')->sum('minutes'),
            'value' => $values->where('paid', true)->sum('value'), 'collected' => $values->sum('collected'),
        ]];
    }
}
