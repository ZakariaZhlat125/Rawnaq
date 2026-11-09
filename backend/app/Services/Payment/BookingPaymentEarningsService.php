<?php

namespace App\Services\Payment;

use App\Models\User;
use Modules\Booking\Models\Booking;

class BookingPaymentEarningsService
{
    public function syncForPaidBookings(array $bookingIds): void
    {
        $bookingIds = array_values(array_unique(array_filter($bookingIds)));

        if (empty($bookingIds)) {
            return;
        }

        $bookings = Booking::with(['bookingService','bookingPackages','commission',])->whereIn('id', $bookingIds)->get();

        foreach ($bookings as $booking) {
            $employeeId = $this->resolveEmployeeId($booking);
            if (! $employeeId) {
                continue;
            }

            $employee = User::role('employee')
                ->where('id', $employeeId)
                ->with('commissions.mainCommission')
                ->first();

            if (! $employee) {
                continue;
            }

            $bookingAmount = $this->resolveBookingAmount($booking);
            $commissionAmount = app(BookingPaymentEarningsCalculator::class)->calculate(
                $bookingAmount,
                $this->commissionRules($employee)
            );

            if ($commissionAmount <= 0) {
                continue;
            }

            if ($booking->commission) {
                $booking->commission->fill([
                    'employee_id' => $employeeId,
                    'commission_amount' => $commissionAmount,
                    'commission_status' => 'unpaid',
                    'payment_date' => null,
                ])->save();

                continue;
            }

            $booking->commission()->create([
                'employee_id' => $employeeId,
                'commission_amount' => $commissionAmount,
                'commission_status' => 'unpaid',
                'payment_date' => null,
            ]);
        }
    }

    private function resolveEmployeeId(Booking $booking): ?int
    {
        $packageEmployeeId = $booking->bookingPackages->first()?->employee_id;
        if ($packageEmployeeId) {
            return (int) $packageEmployeeId;
        }

        $serviceEmployeeId = $booking->bookingService->first()?->employee_id;

        return $serviceEmployeeId ? (int) $serviceEmployeeId : null;
    }

    private function resolveBookingAmount(Booking $booking): float
    {
        $serviceAmount = (float) $booking->bookingService->sum(fn ($service) => (float) ($service->service_price ?? 0));
        $packageAmount = (float) $booking->bookingPackages->sum(fn ($package) => (float) ($package->package_price ?? 0));

        return round($serviceAmount + $packageAmount, 2);
    }

    private function commissionRules(User $employee): array
    {
        return $employee->commissions
            ->filter(fn ($commission) => $commission->mainCommission)
            ->map(fn ($commission) => [
                'type' => $commission->mainCommission->commission_type,
                'value' => $commission->mainCommission->commission_value,
            ])
            ->values()
            ->all();
    }
}
