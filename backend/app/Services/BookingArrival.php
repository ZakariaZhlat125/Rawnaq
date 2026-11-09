<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Booking\Models\Booking;

class BookingArrival
{
    public function update(int $id, bool $arrived, int $actor, string $source): Booking
    {
        return DB::transaction(function () use ($id, $arrived, $actor, $source) {
            $booking = Booking::with(['bookingService', 'bookingPackages', 'user'])->lockForUpdate()->findOrFail($id);
            if (! in_array($booking->status, ['pending', 'confirmed', 'check_in'], true)) {
                throw ValidationException::withMessages(['arrived' => 'لا يمكن تغيير تأكيد الوصول لحجز ملغي أو منتهٍ.']);
            }

            // Same day arrival check
            $bookingDate = \Carbon\Carbon::parse($booking->start_date_time)->toDateString();
            $todayDate = now('Asia/Riyadh')->toDateString();
            if ($arrived && $bookingDate !== $todayDate) {
                throw ValidationException::withMessages(['arrived' => 'يمكن تأكيد وصول العميل في يوم الحجز فقط.']);
            }

            // Repeated scans preserve the original arrival time and confirming employee.
            if ($arrived === (bool) $booking->arrived_at) return $booking;

            $wasArrived = (bool) $booking->arrived_at;
            $booking->arrived_at = $arrived ? now() : null;
            $booking->arrival_confirmed_by = $arrived ? $actor : null;
            $booking->arrival_source = $arrived ? $source : null;
            $booking->save();

            if ($arrived && ! $wasArrived) {
                // 1. Notify queuing system
                try {
                    app(\App\Services\WaitingBookingSyncService::class)->notifyArrival($booking);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Queue arrival notify error: ' . $e->getMessage());
                }

                // 2. Notify assigned employee(s): "وصل عميلك"
                $assignedEmployeeIds = $booking->bookingService->pluck('employee_id')
                    ->merge($booking->bookingPackages->pluck('employee_id'))
                    ->filter()->unique();

                $customerName = trim((string) ($booking->user?->full_name ?? ($booking->user?->first_name . ' ' . $booking->user?->last_name))) ?: 'العميل';

                foreach ($assignedEmployeeIds as $empId) {
                    $employee = \App\Models\User::find($empId);
                    if ($employee) {
                        try {
                            \Illuminate\Support\Facades\Notification::send($employee, new \App\Notifications\CommonNotification('booking_arrival', [
                                'booking_id' => $booking->id,
                                'message' => "وصل عميلك {$customerName} لموعده اليوم.",
                                'subject' => 'وصل عميلك',
                                'user_type' => 'employee',
                            ]));
                        } catch (\Throwable $e) {
                            \Illuminate\Support\Facades\Log::warning('Employee arrival notify error: ' . $e->getMessage());
                        }
                    }
                }
            }

            return $booking;
        });
    }
}
