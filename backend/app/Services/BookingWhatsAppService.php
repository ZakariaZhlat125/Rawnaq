<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Booking\Models\Booking;
use RuntimeException;

class BookingWhatsAppService
{
    public function enqueue(Booking $booking, string $event, ?string $key = null): void
    {
        if (! config('booking_whatsapp.enabled')) {
            return;
        }

        DB::table('booking_whatsapp_notifications')->insertOrIgnore([
            'booking_id' => $booking->id,
            'event' => $event,
            'deduplication_key' => $key ?? ($event === 'created'
                ? "created:{$booking->id}" : "{$event}:{$booking->id}:" . Str::uuid()),
            'booking_start' => $booking->start_date_time,
            'status' => 'pending',
            'attempts' => 0,
            'available_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** Compare actual calendar data, ignoring timestamps and regenerated row IDs. */
    public function fingerprint(Booking $booking): string
    {
        $booking = $booking->fresh();
        $services = DB::table('booking_services')->where('booking_id', $booking->id)
            ->get(['service_id', 'employee_id', 'start_date_time', 'duration_min', 'service_price'])
            ->map(fn ($row) => (array) $row)->sortBy(fn ($row) => json_encode($row))->values()->all();
        $packages = DB::table('booking_packages')->where('booking_id', $booking->id)
            ->get(['package_id', 'employee_id'])->map(fn ($row) => (array) $row)
            ->sortBy(fn ($row) => json_encode($row))->values()->all();

        return hash('sha256', json_encode([
            $booking->only(['start_date_time', 'branch_id', 'user_id', 'note', 'location']),
            $services, $packages,
        ]));
    }

    public function enqueueCalendarUpdate(Booking $booking, string $before): void
    {
        if (config('booking_whatsapp.enabled') && $booking->status !== 'cancelled'
            && $before !== $this->fingerprint($booking)) {
            $this->enqueue($booking->fresh(), 'updated');
        }
    }

    public function appointmentTime(Booking $booking): ?Carbon
    {
        return $booking->start_date_time
            ? Carbon::parse($booking->start_date_time, config('booking_whatsapp.timezone')) : null;
    }

    public function reminderIsDue(Booking $booking): bool
    {
        $start = $this->appointmentTime($booking);

        return ! $booking->trashed() && $booking->status === 'confirmed' && $start
            && $start->isFuture() && $start->copy()->subHours(6)->lessThanOrEqualTo(now());
    }

    public function send(Booking $booking, string $event): string
    {
        $apiKey = trim((string) config('booking_whatsapp.api_key'));
        $template = trim((string) config("booking_whatsapp.templates.{$event}"));
        if ($apiKey === '' || $template === '') {
            throw new RuntimeException('WhatsApp API key or approved template is missing.');
        }

        $booking->loadMissing('user', 'branch');
        $phone = $this->normalizePhone((string) ($booking->user?->mobile ?? ''));
        if ($phone === null) {
            throw new RuntimeException('Booking customer has no valid mobile number.');
        }
        $start = $this->appointmentTime($booking);
        if (! $start) {
            throw new RuntimeException('Booking has no appointment time.');
        }
        $name = trim(($booking->user?->first_name ?? '') . ' ' . ($booking->user?->last_name ?? ''));
        $branch = $booking->branch?->getTranslation('name', config('booking_whatsapp.language'));
        if (is_array($branch)) {
            $branch = $branch['ar'] ?? $branch['en'] ?? reset($branch);
        }
        $parameters = [$name ?: 'عميلنا العزيز', (string) $booking->id,
            $start->format('Y-m-d'), $start->format('H:i'), (string) ($branch ?: 'رونق')];

        // Taqnyat v2 accepts dynamic components at the top level.
        $response = Http::withToken($apiKey)->acceptJson()->connectTimeout(5)->timeout(20)
            ->post('https://api.taqnyat.sa/wa/v2/messages/', [
                'to' => $phone,
                'type' => 'template',
                'template' => ['name' => $template, 'language' => ['code' => config('booking_whatsapp.language')]],
                'components' => [[
                    'type' => 'body',
                    'parameters' => array_map(fn ($value) => ['type' => 'text', 'text' => $value], $parameters),
                ]],
            ]);

        $messageId = $response->json('statuses.message_id');
        if (! $response->successful() || ! is_string($messageId) || $messageId === '') {
            throw new RuntimeException('Taqnyat WhatsApp rejected the request (HTTP ' . $response->status() . ').');
        }

        return $messageId;
    }

    public function normalizePhone(string $phone): ?string
    {
        $phone = strtr($phone, array_combine(preg_split('//u', '٠١٢٣٤٥٦٧٨٩', -1, PREG_SPLIT_NO_EMPTY), range(0, 9)));
        $phone = preg_replace('/[^0-9]/', '', $phone);
        $phone = preg_replace('/^00/', '', $phone);
        if (preg_match('/^05\d{8}$/', $phone)) {
            $phone = '966' . substr($phone, 1);
        } elseif (preg_match('/^5\d{8}$/', $phone)) {
            $phone = '966' . $phone;
        }

        return preg_match('/^[1-9]\d{7,14}$/', $phone) ? $phone : null;
    }
}
