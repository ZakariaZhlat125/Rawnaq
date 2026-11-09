<?php

namespace App\Services;

use App\Models\GiftCard;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Booking\Models\Booking;
use Modules\Service\Models\Service;

class TaqnyatSmsService
{
    protected $apiKey;
    protected $sender;
    protected $baseUrl = 'https://api.taqnyat.sa/v1';
    protected ?string $lastError = null;
    protected static array $sentBookingNotifications = [];

    public function __construct()
    {
        $this->apiKey = trim((string) (setting('taqnyat_api_key') ?: config('services.taqnyat.api_key')));
        $this->sender = trim((string) (setting('taqnyat_sender') ?: config('services.taqnyat.sender', 'SamiCare')));
    }

    public function sendSms($recipients, $message, $sender = null)
    {
        $this->lastError = null;
        $recipientList = $this->normalizeRecipients(is_array($recipients) ? $recipients : [$recipients]);
        $senderName = $this->resolveSenderName($sender);

        $this->giftSmsLog()->info('Preparing Taqnyat SMS request', [
            'recipients' => $recipientList,
            'sender' => $senderName,
            'message_length' => mb_strlen((string) $message),
            'sms_enabled' => (bool) setting('is_taqnyat_sms'),
            'has_api_key' => ! empty($this->apiKey),
        ]);

        if (! setting('is_taqnyat_sms')) {
            $this->fail('taqnyat_disabled', 'Taqnyat SMS is disabled in settings.');
            return $this->localBypass($recipientList, $message);
        }

        if (empty($this->apiKey)) {
            $this->fail('missing_api_key', 'Taqnyat API key is missing.');
            return $this->localBypass($recipientList, $message);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/messages", [
                'recipients' => $recipientList,
                'body' => $message,
                'sender' => $senderName,
            ]);

            if ($response->successful()) {
                $this->giftSmsLog()->info('Taqnyat SMS sent successfully', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);

                return $response->json();
            }

            $this->lastError = 'Taqnyat API failed with HTTP status ' . $response->status();
            $this->giftSmsLog()->error('Taqnyat SMS API returned failure', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            $this->giftSmsLog()->error('Taqnyat SMS exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    public function sendWhatsApp($recipients, $message, $sender = null)
    {
        $this->lastError = null;
        $recipientList = $this->normalizeRecipients(is_array($recipients) ? $recipients : [$recipients]);
        $senderName = $this->resolveSenderName($sender);

        $this->giftSmsLog()->info('Preparing Taqnyat WhatsApp request', [
            'recipients' => $recipientList,
            'sender' => $senderName,
            'message_length' => mb_strlen((string) $message),
            'sms_enabled' => (bool) setting('is_taqnyat_sms'),
            'has_api_key' => ! empty($this->apiKey),
        ]);

        if (! setting('is_taqnyat_sms')) {
            $this->fail('taqnyat_disabled', 'Taqnyat gateway is disabled in settings.');
            return $this->localBypass($recipientList, $message);
        }

        if (empty($this->apiKey)) {
            $this->fail('missing_api_key', 'Taqnyat API key is missing.');
            return $this->localBypass($recipientList, $message);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/messages", [
                'recipients' => $recipientList,
                'body' => $message,
                'sender' => $senderName,
                'mediaType' => 'whatsapp',
                'type' => 'whatsapp',
            ]);

            if ($response->successful()) {
                $this->giftSmsLog()->info('Taqnyat WhatsApp sent successfully', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);
                return $response->json();
            }

            $this->giftSmsLog()->warning('Taqnyat WhatsApp direct returned failure, falling back to SMS', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return $this->sendSms($recipientList, $message, $senderName);
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            $this->giftSmsLog()->error('Taqnyat WhatsApp exception, trying SMS fallback', [
                'message' => $e->getMessage(),
            ]);

            return $this->sendSms($recipientList, $message, $senderName);
        }
    }

    public function sendWelcomeMessage($phone, $name)
    {
        return $this->sendMessageFromSetting($phone, 'taqnyat_welcome_message', [
            'name' => $name,
            'app_name' => setting('app_name'),
        ]);
    }

    public function sendBookingCreatedMessage($phone, $bookingData)
    {
        return $this->sendMessageFromSetting($phone, 'taqnyat_booking_created', [
            'booking_id' => $bookingData['booking_id'] ?? '',
            'booking_date' => $bookingData['booking_date'] ?? '',
            'booking_time' => $bookingData['booking_time'] ?? '',
        ]);
    }

    public function sendBookingCreatedNotification($bookingInput)
    {
        $this->lastError = null;

        $booking = $bookingInput instanceof Booking
            ? $bookingInput
            : Booking::with([
                'user',
                'branch',
                'booking_service.service',
                'booking_service.employee',
                'services',
                'packages',
            ])->find($bookingInput);

        if (! $booking) {
            $this->fail('booking_not_found', 'Booking not found for WhatsApp notification.');
            return false;
        }

        // Prevent duplicate sending for the same booking in one request lifecycle
        if (isset(self::$sentBookingNotifications[$booking->id])) {
            return true;
        }
        self::$sentBookingNotifications[$booking->id] = true;

        if (! $booking->relationLoaded('booking_service')) {
            $booking->load(['user', 'branch', 'booking_service.service', 'booking_service.employee', 'services', 'packages']);
        }

        $user = $booking->user;
        $rawPhone = (string) ($user->mobile ?? $booking->user_phone ?? '');
        $phone = $this->validatePhoneNumber($rawPhone);

        if (! $phone) {
            $this->fail('invalid_phone', "No valid mobile number found for user in booking #{$booking->id}.");
            return false;
        }

        $serviceNamesList = $booking->booking_service
            ->map(fn ($bs) => $bs->service?->name)
            ->filter()
            ->unique()
            ->values();

        if ($serviceNamesList->isEmpty() && $booking->services->isNotEmpty()) {
            $serviceNamesList = $booking->services->map(fn ($s) => $s->name)->filter()->unique()->values();
        }

        $packageNamesList = $booking->packages ? $booking->packages->map(fn ($p) => $p->name)->filter()->unique()->values() : collect();

        $itemNames = $serviceNamesList->map(fn ($n) => $this->resolveDisplayValue($n))->merge($packageNamesList)->implode(', ');
        if (empty($itemNames)) {
            $itemNames = __('booking.service') ?: 'خدمة';
        }

        $employeeNames = $booking->booking_service
            ->map(function ($bs) {
                if (! $bs->employee) {
                    return null;
                }
                $name = trim(($bs->employee->first_name ?? '') . ' ' . ($bs->employee->last_name ?? ''));
                return $name ?: $bs->employee->full_name ?? null;
            })
            ->filter()
            ->unique()
            ->implode(', ');

        if (empty($employeeNames)) {
            $employeeNames = 'غير محدد';
        }

        $startAt = $booking->start_date_time ? Carbon::parse($booking->start_date_time) : null;
        $bookingDate = $startAt ? $startAt->format('Y-m-d') : '';
        $bookingTime = $startAt ? $startAt->format('h:i A') : '';
        $branchName = $booking->branch?->name ?? setting('app_name', 'Rawnaq');
        
        $userName = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : '';
        if (empty($userName)) {
            $userName = $user->full_name ?? 'عميلنا العزيز';
        }

        $totalAmount = number_format((float) ($booking->total_amount ?? $booking->services->sum('service_price') ?? 0), 2);

        $defaultTemplate = "مرحباً [[user_name]] 👋\n\nتم إنشاء وتأكيد حجزك بنجاح في [[app_name]]! 🎉\n\n📋 تفاصيل الحجز:\n• رقم الحجز: #[[booking_id]]\n• الفرع: [[branch_name]]\n• التاريخ: [[booking_date]]\n• الوقت: [[booking_time]]\n• الخدمات: [[service_names]]\n• الأخصائي: [[employee_name]]\n• الإجمالي: [[total_amount]] ريال\n\nنسعد بخدمتك ونتطلع لرؤيتك! 🌸";
        
        $template = setting('taqnyat_booking_created') ?: $defaultTemplate;
        
        // If the old short text is still there, optionally override it for a better WhatsApp message, 
        // but we respect the setting. The variables will be replaced.
        
        $variables = [
            'user_name' => $userName,
            'booking_id' => (string) $booking->id,
            'booking_date' => $bookingDate,
            'booking_time' => $bookingTime,
            'branch_name' => $branchName,
            'service_names' => $itemNames,
            'employee_name' => $employeeNames,
            'total_amount' => $totalAmount,
            'app_name' => setting('app_name', 'Rawnaq'),
        ];

        $message = $this->replaceVariables($template, $variables);

        $this->giftSmsLog()->info('Sending booking created WhatsApp notification', [
            'booking_id' => $booking->id,
            'recipient' => $phone,
        ]);

        return $this->sendWhatsApp($phone, $message);
    }

    public function sendBookingCancelledMessage($phone, $bookingData)
    {
        return $this->sendMessageFromSetting($phone, 'taqnyat_booking_cancelled', [
            'booking_id' => $bookingData['booking_id'] ?? '',
        ]);
    }

    /*public function sendGiftCardRecipientMessage(GiftCard $giftCard)
    {
        $this->giftSmsLog()->info('Building gift card recipient SMS', [
            'gift_card_id' => $giftCard->id,
            'recipient_phone' => $giftCard->recipient_phone,
            'payment_status' => (int) $giftCard->payment_status,
            'gift_status' => $giftCard->gift_status,
            'claim_url' => $giftCard->claim_url,
        ]);

        $serviceNames = $giftCard->services_list
            ->map(fn (Service $service) => $this->resolveDisplayValue($service->name))
            ->filter()
            ->implode(', ');

        $senderName = trim(($giftCard->user?->first_name ?? '') . ' ' . ($giftCard->user?->last_name ?? ''));

        $result = $this->sendMessageFromSetting($giftCard->recipient_phone, 'taqnyat_recipient', [
            'recipient_name' => $giftCard->recipient_name,
            'recipient_phone' => $giftCard->recipient_phone,
            'sender_name' => $senderName,
            'gift_ref' => (string) $giftCard->id,
            'ref' => (string) $giftCard->id,
            'gift_services' => $serviceNames,
            'gift_total' => $this->formatMoney($giftCard->subtotal ?? 0),
            'gift_url' => $giftCard->claim_url ?? '',
            'app_name' => setting('app_name'),
        ]);

        $this->giftSmsLog()->info('Gift card recipient SMS finished', [
            'gift_card_id' => $giftCard->id,
            'sent' => (bool) $result,
            'last_error' => $this->lastError,
        ]);

        return $result;
    }*/

    public function sendGiftCardRecipientMessage(GiftCard $giftCard)
    {
        $this->giftSmsLog()->info('Building gift card recipient SMS', [
            'gift_card_id' => $giftCard->id,
            'recipient_phone' => $giftCard->recipient_phone,
            'payment_status' => (int) $giftCard->payment_status,
            'gift_status' => $giftCard->gift_status,
            'share_url' => $giftCard->share_url,
        ]);

        $serviceNames = $giftCard->services_list
            ->map(fn (Service $service) => $this->resolveDisplayValue($service->name))
            ->filter()
            ->implode(', ');

        $senderName = trim(($giftCard->user?->first_name ?? '') . ' ' . ($giftCard->user?->last_name ?? ''));

        if ($giftCard->type === GiftCard::TYPE_VALUE) {
            $serviceNames = 'بطاقة هدية بقيمة '.$this->formatMoney($giftCard->value_amount ?? 0).' ر.س';
        }
        $code = (string) $giftCard->redeemCode();
        $template = (string) setting('taqnyat_recipient');
        // Templates written before redeem codes existed: append the code so it always reaches the recipient.
        $fallbackSuffix = $code !== '' && trim($template) !== '' && ! str_contains($template, '[[gift_code]]') ? "\nكود الهدية: [[gift_code]]" : '';

        $result = $this->sendMessageFromSetting($giftCard->recipient_phone, 'taqnyat_recipient', [
            'recipient_name' => $giftCard->recipient_name,
            'recipient_phone' => $giftCard->recipient_phone,
            'sender_name' => $senderName,
            'gift_ref' => (string) $giftCard->id,
            'ref' => (string) $giftCard->id,
            'gift_services' => $serviceNames,
            'gift_total' => $this->formatMoney($giftCard->subtotal ?? 0),
            'gift_url' => $giftCard->share_url ?? '',
            'gift_code' => $code,
            'gift_expires_at' => $giftCard->expires_at?->toDateString() ?? '',
            'app_name' => setting('app_name'),
        ], null, $fallbackSuffix);

        $this->giftSmsLog()->info('Gift card recipient SMS finished', [
            'gift_card_id' => $giftCard->id,
            'sent' => (bool) $result,
            'last_error' => $this->lastError,
        ]);

        return $result;
    }

    public function sendMessageFromSetting($recipients, string $settingKey, array $variables = [], ?string $fallback = null, string $suffix = '')
    {
        $message = setting($settingKey, $fallback);
        $message = $this->replaceVariables((string) $message.$suffix, $variables);

        if (trim($message) === '') {
            $this->fail('empty_template', "SMS template [{$settingKey}] is empty.");
            return false;
        }

        $this->giftSmsLog()->debug('Resolved SMS template', [
            'setting_key' => $settingKey,
            'recipients' => is_array($recipients) ? $recipients : [$recipients],
            'message' => $message,
        ]);

        return $this->sendSms($recipients, $message);
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * بيئة local بس: لو مفيش SMS provider مضبوط، منعتبر الإرسال نجح
     * (من غير ما نبعت SMS حقيقي) عشان تصفّحات OTP تفضل قابلة للاختبار
     * محليًا. الإنتاج وأي بيئة تانية بيفضلوا يفشلوا زي ما هما.
     */
    protected function localBypass(array $recipientList, $message)
    {
        if (! app()->environment('local')) {
            return false;
        }

        $this->giftSmsLog()->info('Taqnyat SMS bypassed in local environment (no real SMS sent)', [
            'recipients' => $recipientList,
            'message' => $message,
        ]);

        return ['status' => 'bypassed_local'];
    }

    protected function fail(string $code, string $message): void
    {
        $this->lastError = $message;

        $this->giftSmsLog()->warning('Gift SMS skipped or failed before API request', [
            'code' => $code,
            'message' => $message,
        ]);
    }

    protected function giftSmsLog()
    {
        return Log::channel('gift_sms');
    }

    protected function replaceVariables($message, $variables)
    {
        foreach ($variables as $key => $value) {
            $message = str_replace("[[{$key}]]", (string) $value, $message);
        }

        return $message;
    }

    protected function resolveDisplayValue($value): string
    {
        if (is_array($value)) {
            $locale = app()->getLocale();
            $translated = $value[$locale] ?? $value['ar'] ?? $value['en'] ?? reset($value);

            return is_string($translated) ? trim($translated) : '';
        }

        return is_string($value) ? trim($value) : '';
    }

    protected function formatMoney($amount): string
    {
        $amount = (float) $amount;

        return floor($amount) == $amount ? (string) (int) $amount : number_format($amount, 2, '.', '');
    }

    protected function normalizeRecipients(array $recipients): array
    {
        return collect($recipients)
            ->map(function ($phone) {
                $phone = preg_replace('/[^0-9]/', '', (string) $phone);

                if (preg_match('/^00(9665[0-9]{8})$/', $phone, $matches)) {
                    return $matches[1];
                }

                if (preg_match('/^9665[0-9]{8}$/', $phone)) {
                    return $phone;
                }

                if (preg_match('/^05([0-9]{8})$/', $phone, $matches)) {
                    return '9665' . $matches[1];
                }

                if (preg_match('/^5[0-9]{8}$/', $phone)) {
                    return '966' . $phone;
                }

                return $phone;
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function resolveSenderName(?string $sender = null): string
    {
        $senderName = trim((string) ($sender ?: $this->sender));

        if ($this->isInvalidSenderName($senderName)) {
            $fallback = trim((string) config('services.taqnyat.sender', 'SamiCare'));

            $this->giftSmsLog()->warning('Invalid Taqnyat sender setting ignored', [
                'configured_sender' => $senderName,
                'fallback_sender' => $fallback,
            ]);

            return $fallback !== '' ? $fallback : 'SamiCare';
        }

        return $senderName;
    }

    protected function isInvalidSenderName(string $senderName): bool
    {
        return $senderName === ''
            || mb_strlen($senderName) > 50
            || str_contains($senderName, '[[')
            || str_contains($senderName, ']]')
            || preg_match('/https?:\/\//i', $senderName);
    }

    public function validatePhoneNumber($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (preg_match('/^(966)/', $phone)) {
            $phone = '0' . substr($phone, 3);
        }

        if (preg_match('/^5[0-9]{8}$/', $phone)) {
            $phone = '0' . $phone;
        }

        if (preg_match('/^05[0-9]{8}$/', $phone)) {
            return $phone;
        }

        return false;
    }
}
