<?php

namespace App\Services\Payment;

use App\Models\GiftCard;
use App\Models\Invoice;
use App\Services\TaqnyatSmsService;
use Carbon\Carbon;
use Modules\Booking\Models\Booking;
use Modules\Package\Models\BookingPackages;
use Modules\Product\Models\OrderGroup;

class PaymentSuccessSmsService
{
    public function __construct(private readonly TaqnyatSmsService $smsService)
    {
    }

    public function sendForInvoice(int $invoiceId): void
    {
        if (! setting('is_taqnyat_sms')) {
            return;
        }

        $invoice = Invoice::with('user')->find($invoiceId);

        if (! $invoice || ! $invoice->user) {
            return;
        }

        $phone = $this->smsService->validatePhoneNumber((string) ($invoice->user->mobile ?? ''));
        if (! $phone) {
            return;
        }

        $bookings = $this->loadBookings($invoice->cart_ids ?? []);
        $serviceBookings = $bookings->filter(fn (Booking $booking) => $booking->booking_service->isNotEmpty())->values();
        $packageBookingRows = $this->loadPackageRows($invoice->cart_ids ?? []);
        $orderGroups = $this->loadOrderGroups($invoice->product_ids ?? []);
        $giftCards = $this->loadGiftCards($invoice->gift_ids ?? []);

        $baseVariables = [
            'user_name' => $this->resolveUserName($invoice),
            'app_name' => setting('app_name'),
            'invoice_id' => (string) $invoice->id,
            'invoice_code' => (string) ((setting('inv_prefix') ?? 'INV-') . $invoice->id),
            'total_amount' => $this->formatMoney($invoice->final_total ?? 0),
            'payment_method' => (string) ($invoice->payment_method ?? ''),
        ];

        $messages = [];
        if ($serviceBookings->isNotEmpty()) {
            $messages[] = $this->renderMessage('taqnyat_payment_service_booking', array_merge(
                $baseVariables,
                $this->buildServiceVariables($serviceBookings)
            ));
        }

        if ($packageBookingRows->isNotEmpty()) {
            $messages[] = $this->renderMessage('taqnyat_payment_package_booking', array_merge(
                $baseVariables,
                $this->buildPackageVariables($packageBookingRows)
            ));
        }

        if ($orderGroups->isNotEmpty()) {
            $messages[] = $this->renderMessage('taqnyat_payment_product_order', array_merge(
                $baseVariables,
                $this->buildProductVariables($orderGroups)
            ));
        }

        if ($giftCards->isNotEmpty()) {
            $messages[] = $this->renderMessage('taqnyat_payment_gift_card', array_merge(
                $baseVariables,
                $this->buildGiftVariables($giftCards)
            ));
        }

        $message = implode("\n", array_filter($messages));
        if ($message !== '') {
            $this->smsService->sendSms($phone, $message);
        }
    }

    private function renderMessage(string $key, array $variables): string
    {
        $fallbacks = [
            'taqnyat_payment_service_booking' => 'تم تأكيد حجزك. الفاتورة [[invoice_code]]. الخدمات: [[service_names]]. التاريخ: [[booking_dates]]. الوقت: [[booking_times]]. الفرع: [[branch_names]]. الإجمالي: [[total_amount]] ريال.',
            'taqnyat_payment_package_booking' => 'الباقات: [[package_names]]. التاريخ: [[booking_dates]]. الوقت: [[booking_times]]. الفرع: [[branch_names]].',
            'taqnyat_payment_product_order' => 'المنتجات: [[product_names]]. الطلبات: [[order_codes]].',
            'taqnyat_payment_gift_card' => 'الهدايا: [[gift_services]]. المستلم: [[recipient_names]]. [[gift_urls]]',
        ];
        $template = (string) (setting($key) ?: $fallbacks[$key]);
        foreach ($variables as $name => $value) {
            $template = str_replace('[['.$name.']]', (string) $value, $template);
        }
        return trim($template);
    }

    private function loadBookings(array $bookingIds)
    {
        if (empty($bookingIds)) {
            return collect();
        }

        return Booking::with([
            'branch:id,name',
            'booking_service.service:id,name',
        ])->whereIn('id', $bookingIds)->get();
    }

    private function loadPackageRows(array $bookingIds)
    {
        if (empty($bookingIds)) {
            return collect();
        }

        return BookingPackages::with([
            'booking.branch:id,name',
            'package:id,name',
        ])->whereIn('booking_id', $bookingIds)->get();
    }

    private function loadOrderGroups(array $orderGroupIds)
    {
        if (empty($orderGroupIds)) {
            return collect();
        }

        return OrderGroup::with('order.orderItems.product')
            ->whereIn('id', $orderGroupIds)
            ->get();
    }

    private function loadGiftCards(array $giftIds)
    {
        if (empty($giftIds)) {
            return collect();
        }

        return GiftCard::whereIn('id', $giftIds)->get();
    }

    private function buildServiceVariables($serviceBookings): array
    {
        $serviceNames = $serviceBookings
            ->flatMap(fn (Booking $booking) => $booking->booking_service->map(
                fn ($item) => $this->resolveDisplayValue($item->service?->name)
            ))
            ->filter()
            ->unique()
            ->values();

        return [
            'booking_ids' => $serviceBookings->pluck('id')->implode($this->separator()),
            'booking_dates' => $this->implodeDateValues($serviceBookings->pluck('start_date_time')),
            'booking_times' => $this->implodeTimeValues($serviceBookings->pluck('start_date_time')),
            'branch_names' => $serviceBookings->pluck('branch.name')->filter()->unique()->implode($this->separator()),
            'service_names' => $serviceNames->implode($this->separator()),
            'services_count' => (string) $serviceBookings->sum(fn (Booking $booking) => $booking->booking_service->count()),
        ];
    }

    private function buildPackageVariables($packageRows): array
    {
        $packageNames = $packageRows
            ->map(fn (BookingPackages $row) => $this->resolveDisplayValue($row->package?->name))
            ->filter()
            ->unique()
            ->values();

        $bookings = $packageRows->pluck('booking')->filter();

        return [
            'booking_ids' => $packageRows->pluck('booking_id')->unique()->implode($this->separator()),
            'booking_dates' => $this->implodeDateValues($bookings->pluck('start_date_time')),
            'booking_times' => $this->implodeTimeValues($bookings->pluck('start_date_time')),
            'branch_names' => $bookings->pluck('branch.name')->filter()->unique()->implode($this->separator()),
            'package_names' => $packageNames->implode($this->separator()),
            'packages_count' => (string) $packageNames->count(),
        ];
    }

    private function buildProductVariables($orderGroups): array
    {
        $orderCodes = $orderGroups
            ->pluck('order_code')
            ->filter()
            ->map(fn ($code) => (string) ((setting('inv_prefix') ?? '') . $code))
            ->values();

        $productNames = $orderGroups
            ->flatMap(fn (OrderGroup $group) => optional($group->order)->orderItems ?? collect())
            ->map(fn ($item) => $item->product?->name)
            ->map(fn ($name) => $this->resolveDisplayValue($name))
            ->filter()
            ->unique()
            ->values();

        return [
            'order_codes' => $orderCodes->implode($this->separator()),
            'product_names' => $productNames->implode($this->separator()),
            'products_count' => (string) $productNames->count(),
        ];
    }

    private function buildGiftVariables($giftCards): array
    {
        $giftServices = $giftCards
            ->flatMap(fn (GiftCard $giftCard) => $giftCard->services_list)
            ->map(fn ($service) => $this->resolveDisplayValue($service->name))
            ->filter()
            ->unique()
            ->values();

        return [
            'gift_ids' => $giftCards->pluck('id')->implode($this->separator()),
            'recipient_names' => $giftCards->pluck('recipient_name')->filter()->unique()->implode($this->separator()),
            'recipient_phones' => $giftCards->pluck('recipient_phone')->filter()->unique()->implode($this->separator()),
            'gift_services' => $giftServices->implode($this->separator()),
            'gifts_count' => (string) $giftCards->count(),
            'gift_urls' => $giftCards->map(fn (GiftCard $giftCard) => $giftCard->claim_url)->filter()->implode($this->separator()),
        ];
    }

    private function resolveUserName(Invoice $invoice): string
    {
        $user = $invoice->user;
        $name = trim((string) (($user->first_name ?? '') . ' ' . ($user->last_name ?? '')));

        if ($name !== '') {
            return $name;
        }

        return trim((string) ($user->full_name ?? $user->mobile ?? 'عميلنا العزيز'));
    }

    private function resolveDisplayValue($value): string
    {
        if (is_array($value)) {
            $locale = app()->getLocale();
            $translated = $value[$locale] ?? reset($value);

            return is_string($translated) ? trim($translated) : '';
        }

        return is_string($value) ? trim($value) : '';
    }

    private function implodeDateValues($values): string
    {
        return collect($values)
            ->filter()
            ->map(fn ($value) => Carbon::parse($value)->locale(app()->getLocale())->translatedFormat('l، j F Y'))
            ->unique()
            ->implode($this->separator());
    }

    private function implodeTimeValues($values): string
    {
        return collect($values)
            ->filter()
            ->map(fn ($value) => Carbon::parse($value)->locale(app()->getLocale())->translatedFormat('g:i A'))
            ->unique()
            ->implode($this->separator());
    }

    private function formatMoney($amount): string
    {
        $amount = (float) $amount;

        return floor($amount) == $amount ? (string) (int) $amount : number_format($amount, 2, '.', '');
    }

    private function separator(): string
    {
        return app()->getLocale() === 'ar' ? '، ' : ', ';
    }
}
