<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GiftCard;
use App\Models\LoyaltyPoint;
use App\Models\reject;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Booking\Models\Booking;
use Modules\Package\Models\UserPackage;
use Modules\Product\Models\Order;
use Modules\Promotion\Models\Coupon;
use Modules\Wallet\Models\Wallet;

class ProfileController extends Controller
{
    public function show(): JsonResponse
    {
        $user = Auth::user()->loadMissing('affiliate');

        $bookingsQuery = Booking::query()
            ->with(['branch', 'services.employee', 'services.service', 'bookingTransaction'])
            //->where('created_by', $user->id)
            ->where('user_id', $user->id)
            ->whereHas('services')
            ->whereNull('deleted_by')
            ->where(function ($q) {
                $q->where('payment_type', '!=', 'cart')
                    ->orWhereHas('transactions', function ($t) {
                        $t->where('payment_status', 1);
                    });
            });

        $allBookings = (clone $bookingsQuery)->latest('id')->get();
        $currentBookings = (clone $bookingsQuery)
            ->whereNotIn('status', ['completed', 'canceled', 'cancelled'])
            ->latest('id')
            ->get();
        $completedBookings = (clone $bookingsQuery)
            ->paid()
            ->where('status', 'completed')
            ->latest('id')
            ->get();

        $giftCards = GiftCard::query()
            ->where('user_id', $user->id)
            ->where('payment_status', 1)
            ->latest('id')
            ->get();

        /* UserPackage بتتعمل وقت الإضافة للسلة مش وقت الدفع، فلازم نتأكد إن الحجز المرتبط
           بيها فعلاً مدفوع (paid) وإلا هنعرض باقات لسه في السلة وكأنها اتشرت فعلاً. */
        $userPackages = UserPackage::query()
            ->with('package')
            ->where('user_id', $user->id)
            ->whereHas('booking', fn ($q) => $q->paid())
            ->latest('id')
            ->get();

        $orders = Order::query()
            ->with('orderItems.product')
            ->where('user_id', $user->id)
            ->where('payment_status', 'paid')
            ->latest('id')
            ->get();

        $coupons = Coupon::query()
            ->with('promotion')
            ->where('is_expired', 0)
            ->where('use_limit', '>=', 1)
            ->latest('id')
            ->get();

        $walletSummary = app(\App\Services\Wallet\WalletLedger::class)->summary($user->id);
        $walletBalance = $walletSummary['active'] ? (float) $walletSummary['balance'] : 0.0;
        $loyaltyPoints = (int) (LoyaltyPoint::where('user_id', $user->id)->value('points') ?? 0);

        return response()->json([
            'status' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'referral_code' => $user->referral_code,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                    'email' => $user->email,
                    'mobile' => $user->mobile,
                    'profile_image' => $this->resolveUrl($user->avatar ?: $user->profile_image),
                    'date_of_birth' => optional($user->date_of_birth)->format('Y-m-d') ?: $user->date_of_birth,
                    'city' => $user->city ?? '',
                    'country' => $user->country ?? '',
                    'address' => $user->address ?? '',
                ],
                'balances' => [
                    'wallet' => $walletBalance,
                    'wallet_cash' => $walletSummary['active'] ? $walletSummary['cash_balance'] : 0.0,
                    'wallet_bonus' => $walletSummary['active'] ? $walletSummary['bonus_balance'] : 0.0,
                    'loyalty_points' => $loyaltyPoints,
                ],
                'stats' => [
                    'coupons_count' => $coupons->count(),
                    'pending_bookings_count' => $currentBookings->count(),
                    'completed_bookings_count' => $completedBookings->count(),
                    'gift_cards_count' => $giftCards->count(),
                    'packages_count' => $userPackages->count(),
                    'orders_count' => $orders->count(),
                ],
                'payment_methods' => [
                    ['key' => 'visa', 'image_url' => asset('images/icons/visa.png')],
                    ['key' => 'mada', 'image_url' => asset('images/icons/mada.png')],
                    ['key' => 'tamara', 'image_url' => asset('images/icons/tamara.png')],
                    ['key' => 'tabby', 'image_url' => asset('images/icons/tabby.png')],
                ],
                'transactions' => $allBookings
                    ->flatMap(fn(Booking $booking) => $booking->services->map(function ($service) use ($booking) {
                        return [
                            'booking_id' => $booking->id,
                            'service_id' => $service->service_id,
                            'service_name' => $service->service_name,
                            'branch_name' => $this->localizedValue($booking->branch?->name),
                            'date' => $this->formatDate($booking->start_date_time) ?: $this->formatDate($booking->created_at),
                            'status' => $booking->status,
                        ];
                    }))
                    ->values(),
                'current_bookings' => $currentBookings->map(fn(Booking $booking) => $this->transformBooking($booking, true))->values(),
                'completed_bookings' => $completedBookings->map(fn(Booking $booking) => $this->transformBooking($booking, false))->values(),
                'gift_cards' => $giftCards->map(fn(GiftCard $giftCard) => $this->transformGiftCard($giftCard))->values(),
                'purchased_packages' => $userPackages->map(fn(UserPackage $userPackage) => [
                    'id' => $userPackage->id,
                    'package_id' => $userPackage->package_id,
                    'name' => $this->localizedValue($userPackage->package?->name),
                    'image' => $userPackage->package?->feature_image,
                    'price' => (float) $userPackage->package_price,
                    'purchase_date' => $this->formatDate($userPackage->purchase_date) ?: $this->formatDate($userPackage->created_at),
                ])->values(),
                'purchased_products' => $orders->flatMap(fn(Order $order) => $order->orderItems->map(function ($item) use ($order) {
                    return [
                        'order_id' => $order->id,
                        'product_id' => $item->product_id,
                        'name' => $this->localizedValue($item->product?->name),
                        'image' => $item->product?->feature_image,
                        'qty' => (int) $item->qty,
                        'unit_price' => (float) $item->unit_price,
                        'total_price' => (float) $item->total_price,
                        'purchase_date' => $this->formatDate($order->created_at),
                    ];
                }))->values(),
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = Auth::user();

        $data = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'mobile' => 'required|string|max:20|unique:users,mobile,' . $user->id,
            'email' => 'nullable|email|max:255|unique:users,email,' . $user->id,
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date|before:today',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($request->hasFile('profile_image')) {
            $image = $request->file('profile_image');
            $imageName = 'user_' . $user->id . '_' . time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('profile_images'), $imageName);
            $data['avatar'] = 'profile_images/' . $imageName;
        }

        $user->update($data);

        return response()->json([
            'status' => true,
            'message' => __('messages.profile_updated'),
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                    'email' => $user->email,
                    'mobile' => $user->mobile,
                    'avatar' => $this->resolveUrl($user->avatar ?: $user->profile_image),
                    'profile_image' => $this->resolveUrl($user->profile_image),
                    'date_of_birth' => $this->formatDate($user->date_of_birth),
                    'city' => $user->city,
                    'country' => $user->country,
                    'address' => $user->address,
                ],
            ],
        ]);
    }

    private function transformBooking(Booking $booking, bool $canCancel): array
    {
        return [
            'id' => $booking->id,
            'arrived_at' => $booking->arrived_at?->toIso8601String(),
            'arrival_qr_url' => in_array($booking->status, ['pending', 'confirmed', 'check_in'], true)
                ? \Illuminate\Support\Facades\URL::signedRoute('backend.booking-arrival.scan', ['booking' => $booking->id]) : null,
            'status' => $booking->status,
            'payment_status' => (int) $booking->payment_status,
            'branch' => [
                'id' => $booking->branch_id,
                'name' => $this->localizedValue($booking->branch?->name),
            ],
            'start_date' => $this->formatDate($booking->start_date_time, 'd-m-Y'),
            'start_time' => $this->formatDate($booking->start_date_time, 'H:i'),
            'can_cancel' => $canCancel,
            'services' => $booking->services->map(function ($service) {
                return [
                    'id' => $service->id,
                    'service_id' => $service->service_id,
                    'service_name' => $service->service_name,
                    'employee_id' => $service->employee_id,
                    'employee_name' => $service->employee->full_name ?? trim(($service->employee->first_name ?? '') . ' ' . ($service->employee->last_name ?? '')),
                    'price' => (float) ($service->service->default_price ?? $service->service_price ?? 0),
                ];
            })->values(),
        ];
    }

    private function transformGiftCard(GiftCard $giftCard): array
    {
        return [
            'id' => $giftCard->id,
            'recipient_name' => $giftCard->recipient_name,
            'recipient_phone' => $giftCard->recipient_phone,
            'message' => $giftCard->message,
            'payment_status' => (int) $giftCard->payment_status,
            'gift_status' => $giftCard->gift_status,
            'claim_url' => $giftCard->claim_url,
            'send_status' => $giftCard->send_status,
            'sent_at' => optional($giftCard->sent_at)->format('Y-m-d H:i:s'),
            'claimed_at' => optional($giftCard->claimed_at)->format('Y-m-d H:i:s'),
            'created_at' => optional($giftCard->created_at)->format('Y-m-d H:i:s'),
            'services' => collect($giftCard->services_list ?? [])
                ->map(function ($service) {
                    return [
                        'id' => data_get($service, 'id'),
                        'name' => $this->localizedValue(data_get($service, 'name')),
                    ];
                })
                ->values(),
        ];
    }

    private function localizedValue(mixed $value): ?string
    {
        if (is_array($value)) {
            $locale = app()->getLocale();

            return $value[$locale] ?? $value['ar'] ?? $value['en'] ?? reset($value) ?: null;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $locale = app()->getLocale();

                return $decoded[$locale] ?? $decoded['ar'] ?? $decoded['en'] ?? reset($decoded) ?: null;
            }
        }

        return filled($value) ? (string) $value : null;
    }

    private function formatDate(mixed $value, string $format = 'Y-m-d'): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->format($format);
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return preg_match('/^https?:\/\//i', $path) ? $path : asset($path);
    }
}
