<?php

namespace App\Console\Commands;

use App\Services\BookingWhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Booking\Models\Booking;
use Throwable;

class SendBookingWhatsAppNotifications extends Command
{
    protected $signature = 'bookings:send-whatsapp';
    protected $description = 'Send pending booking WhatsApp messages and reminders six hours before appointments';

    public function handle(BookingWhatsAppService $service): int
    {
        if (! config('booking_whatsapp.enabled')) {
            $this->info('Booking WhatsApp notifications are disabled.');
            return self::SUCCESS;
        }
        if (! config('booking_whatsapp.api_key')) {
            $this->error('TAQNYAT_WHATSAPP_API_KEY is required.');
            return self::FAILURE;
        }

        $localNow = now(config('booking_whatsapp.timezone'));
        Booking::where('status', 'confirmed')
            ->where('start_date_time', '>', $localNow->format('Y-m-d H:i:s'))
            ->where('start_date_time', '<=', $localNow->copy()->addHours(6)->format('Y-m-d H:i:s'))
            ->chunkById(100, function ($bookings) use ($service) {
                foreach ($bookings as $booking) {
                    $key = 'reminder:' . $booking->id . ':' . $service->appointmentTime($booking)->format('YmdHis');
                    $service->enqueue($booking, 'reminder', $key);
                }
            });

        DB::table('booking_whatsapp_notifications')->where('status', 'pending')
            ->where('available_at', '<=', now())->orderBy('id')
            ->chunkById(100, function ($rows) use ($service) {
                foreach ($rows as $row) {
                    $this->deliver((int) $row->id, (int) $row->booking_id, $service);
                }
            });

        return self::SUCCESS;
    }

    private function deliver(int $id, int $bookingId, BookingWhatsAppService $service): void
    {
        // Lock the booking first so concurrent calendar edits cannot send an old reminder.
        // No transaction retries here: an HTTP request must not be replayed on a deadlock.
        DB::transaction(function () use ($id, $bookingId, $service) {
            $booking = Booking::withTrashed()->whereKey($bookingId)->lockForUpdate()->first();
            $row = DB::table('booking_whatsapp_notifications')->where('id', $id)->lockForUpdate()->first();
            if (! $row || $row->status !== 'pending' || $row->available_at > now()->format('Y-m-d H:i:s')) {
                return;
            }
            $start = $booking ? $service->appointmentTime($booking) : null;
            $skip = ! $booking;
            if ($booking && $row->event === 'cancelled') {
                $skip = ! $booking->trashed() && $booking->status !== 'cancelled';
            } elseif ($booking) {
                $skip = $booking->trashed() || ! in_array($booking->status, ['confirmed', 'check_in'], true)
                    || ! $start || ! $start->isFuture();
                if ($row->event === 'reminder') {
                    $skip = $skip || ! $service->reminderIsDue($booking)
                        || $row->booking_start !== $start->format('Y-m-d H:i:s');
                }
            }
            if ($skip) {
                DB::table('booking_whatsapp_notifications')->where('id', $id)->update([
                    'status' => 'skipped', 'updated_at' => now(),
                ]);
                return;
            }

            try {
                $messageId = $service->send($booking, $row->event);
            } catch (Throwable $e) {
                $attempts = $row->attempts + 1;
                // Do not persist HTTP exception bodies, tokens, phone numbers or message content.
                $error = get_class($e) === \RuntimeException::class
                    ? $e->getMessage() : 'WhatsApp transport failed; check provider connectivity.';
                DB::table('booking_whatsapp_notifications')->where('id', $id)->update([
                    'status' => $attempts >= 5 ? 'failed' : 'pending', 'attempts' => $attempts,
                    'available_at' => now()->addMinutes(min(60, 2 ** $attempts)),
                    'last_error' => $error, 'updated_at' => now(),
                ]);
                Log::warning('Booking WhatsApp send failed', ['notification_id' => $id, 'error' => $error]);
                return;
            }
            DB::table('booking_whatsapp_notifications')->where('id', $id)->update([
                'status' => 'sent', 'sent_at' => now(), 'updated_at' => now(),
                'provider_message_id' => $messageId, 'last_error' => null,
                'attempts' => $row->attempts + 1,
            ]);
        });
    }
}
