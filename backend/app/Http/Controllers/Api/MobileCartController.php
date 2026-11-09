<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GiftCard;
use App\Services\CartExpirationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingService;
use Modules\Package\Models\BookingPackages;
use Modules\Package\Models\Package;
use Modules\Package\Models\UserPackage;
use Modules\Product\Models\Cart;
use Modules\Service\Models\Service;

class MobileCartController extends Controller
{
    public function syncProducts(Request $request)
    {
        $data = $request->validate([
            'products' => 'present|array|max:100',
            'products.*' => 'required|array:product_id,qty',
            'products.*.product_id' => 'required|integer|distinct|min:1',
            'products.*.qty' => 'required|integer|min:1|max:1000',
        ]);
        DB::transaction(function () use ($request, $data) {
            $products = \Modules\Product\Models\Product::whereIn('id', array_column($data['products'], 'product_id'))
                ->where('status', 1)->lockForUpdate()->get()->keyBy('id');
            foreach ($data['products'] as $item) {
                $product = $products->get($item['product_id']);
                if (!$product || $item['qty'] > (int) $product->stock_qty
                    || ($product->max_purchase_qty > 0 && $item['qty'] > $product->max_purchase_qty)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'products' => app()->getLocale() === 'en' ? 'A selected product or quantity is unavailable.' : 'أحد المنتجات المختارة أو الكمية المطلوبة غير متاح.',
                    ]);
                }
            }
            Cart::where('user_id', $request->user()->id)->delete();
            foreach ($data['products'] as $item) {
                Cart::create([
                    'product_id' => $item['product_id'], 'qty' => $item['qty'],
                    'user_id' => $request->user()->id,
                    'location_id' => $products->get($item['product_id'])->branch_id ?: 1,
                ]);
            }
        });
        return response()->json(['status' => true]);
    }

    public function index(Request $request)
    {
        $userId = $request->user()->id;
        app(CartExpirationService::class)->clearExpired($userId);

        $bookings = Booking::with(['service.service', 'service.employee', 'services', 'bookingTransaction'])
            ->where('created_by', $userId)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->where('payment_type', 'cart')
            ->unpaid()
            ->whereNull('deleted_by')
            ->get();

        $products = Cart::with('product')
            ->where('user_id', $userId)
            ->get();

        $giftCards = GiftCard::where('user_id', $userId)
            ->where('payment_status', 0)
            ->get();

        $packageBookings = BookingPackages::query()
            ->whereHas('booking', function ($query) use ($userId) {
                $query->where('created_by', $userId)
                    ->whereNotIn('status', ['cancelled', 'completed'])
                    ->where('payment_type', 'cart')
                    ->unpaid()
                    ->whereNull('deleted_by');
            })
            ->with(['package', 'booking.branch', 'employee'])
            ->get();

        $serviceTotal = (float) $bookings->sum(function ($booking) {
            return $booking->service ? ((float) ($booking->service->service_price ?? 0)) : 0;
        });

        $productTotal = (float) $products->sum(function ($item) {
            $price = (float) ($item->product->min_price ?? $item->product->max_price ?? 0);
            return $price * ((int) ($item->qty ?? 1));
        });

        $giftTotal = (float) $giftCards->sum(fn ($gift) => (float) ($gift->subtotal ?? 0));
        $packageTotal = (float) $packageBookings->sum(fn ($item) => (float) ($item->package_price ?? $item->package?->package_price ?? 0));

        $discountTotal = (float) $bookings->sum(function ($booking) {
            return (float) $booking->services->sum(fn ($service) => (float) ($service->discount_amount ?? 0));
        });

        $cartTotal = $serviceTotal + $productTotal + $giftTotal + $packageTotal;
        $finalTotal = $cartTotal - $discountTotal;

        return response()->json([
            'success' => true,
            'data' => [
                'bookings' => $bookings,
                'products' => $products,
                'gift_cards' => $giftCards,
                'packages' => $packageBookings->map(function (BookingPackages $item) {
                    $package = $item->package;

                    return [
                        'id' => $item->id,
                        'booking_id' => $item->booking_id,
                        'package_id' => $item->package_id,
                        'type' => $package->type ?? Package::TYPE_PACKAGE,
                        'name' => $this->localizedValue($package->name),
                        'price' => (float) ($item->package_price ?? $package->package_price ?? 0),
                        'branch' => [
                            'id' => $item->booking?->branch_id,
                            'name' => $this->localizedValue($item->booking?->branch?->name),
                        ],
                        'employee' => [
                            'id' => $item->employee_id,
                            'name' => $item->employee?->full_name ?? trim(($item->employee?->first_name ?? '').' '.($item->employee?->last_name ?? '')),
                        ],
                        'booking_date' => $this->formatDate($item->booking?->start_date_time, 'Y-m-d'),
                        'booking_time' => $this->formatDate($item->booking?->start_date_time, 'H:i'),
                        'notes' => $item->booking?->note,
                        'image' => $package?->feature_image,
                    ];
                })->values(),
                'summary' => [
                    'bookings_count' => $bookings->count(),
                    'products_count' => $products->count(),
                    'gift_cards_count' => $giftCards->count(),
                    'packages_count' => $packageBookings->count(),
                    'service_total' => $serviceTotal,
                    'product_total' => $productTotal,
                    'gift_total' => $giftTotal,
                    'package_total' => $packageTotal,
                    'discount_total' => $discountTotal,
                    'cart_total' => $cartTotal,
                    'final_total' => $finalTotal,
                ],
            ],
        ]);
    }

    public function storePackage(Request $request)
    {
        $validated = $request->validate([
            'package_id' => ['required', 'integer', 'exists:packages,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'time' => ['required', 'date_format:H:i'],
            'employee_id' => ['nullable', 'integer'],
            'employye_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();
        $package = Package::query()->findOrFail($validated['package_id']);

        if (! $package->isActiveForFrontend()) {
            return response()->json([
                'success' => false,
                'message' => __('package.package_not_available'),
            ], 422);
        }

        $employeeId = (int) ($validated['employee_id'] ?? $validated['employye_id'] ?? 0);
        $branchId = (int) ($validated['branch_id'] ?? $package->branch_id ?? 0);
        if ($branchId > 0 && !\App\Models\Branch::whereKey($branchId)->where('status', 1)->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['branch_id' => 'غير متاح الحجز في الوقت الحالي']);
        }

        if ($branchId <= 0) {
            return response()->json([
                'success' => false,
                'message' => __('Branch is required.'),
            ], 422);
        }

        if ($employeeId <= 0) {
            return response()->json([
                'success' => false,
                'message' => __('Employee is required.'),
            ], 422);
        }

        $startDateTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $validated['date'].' '.$validated['time']);

        $data = DB::transaction(function () use ($user, $package, $branchId, $employeeId, $validated, $startDateTime) {
            $selectedEmployee = User::role('employee')->whereKey($employeeId)->lockForUpdate()->first();
            if (!$selectedEmployee || !(int) $selectedEmployee->status || (int) $selectedEmployee->is_banned) {
                throw \Illuminate\Validation\ValidationException::withMessages(['employee_id' => 'الموظف المختار موقوف أو غير متاح.']);
            }
            $booking = Booking::create([
                'status' => 'pending',
                'start_date_time' => $startDateTime,
                'user_id' => $user->id,
                'branch_id' => $branchId,
                'note' => $validated['notes'] ?? null,
                'created_by' => $user->id,
                'payment_type' => 'cart',
            ]);

            $bookingPackage = BookingPackages::create([
                'booking_id' => $booking->id,
                'package_id' => $package->id,
                'employee_id' => $employeeId,
                'user_id' => $user->id,
                'package_price' => (float) ($package->package_price ?? 0),
                'created_by' => $user->id,
            ]);

            UserPackage::create([
                'booking_id' => $booking->id,
                'employee_id' => $employeeId,
                'user_id' => $user->id,
                'package_price' => (float) ($package->package_price ?? 0),
                'purchase_date' => now(),
                'package_id' => $package->id,
            ]);

            return [
                'booking' => $booking,
                'booking_package' => $bookingPackage,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => __('messages.booking_added_to_cart'),
            'data' => [
                'booking_id' => $data['booking']->id,
                'booking_package_id' => $data['booking_package']->id,
                'package_id' => $package->id,
                'package_type' => $package->type ?? Package::TYPE_PACKAGE,
                'package_name' => $this->localizedValue($package->name),
                'package_price' => (float) ($package->package_price ?? 0),
                'branch_id' => $branchId,
                'employee_id' => $employeeId,
                'date' => $validated['date'],
                'time' => $validated['time'],
            ],
        ], 201);
    }

    public function storeBooking(Request $request)
    {
        $validated = $request->validate([
            'branch' => ['required', 'integer', 'min:0'],
            'city_id' => ['required_if:branch,0', 'nullable', 'integer'],
            'latitude' => ['required_if:branch,0', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['required_if:branch,0', 'nullable', 'numeric', 'between:-180,180'],
            'services' => ['required', 'array', 'min:1'],
            'services.*.subServices' => ['required', 'array', 'min:1'],
            'services.*.subServices.*.id' => ['required', 'integer', 'exists:services,id'],
            'services.*.subServices.*.repeat_reward_id' => ['nullable', 'integer', 'min:1'],
            'services.*.subServices.*.date' => ['required', 'date_format:Y-m-d'],
            'services.*.subServices.*.time' => ['required', 'date_format:H:i'],
            'services.*.subServices.*.duration' => ['nullable', 'integer', 'min:1'],
            'services.*.subServices.*.staffId' => ['required', 'integer', 'exists:users,id'],
            'customerName' => ['nullable', 'string'],
            'mobileNo' => ['nullable', 'string'],
            'neighborhood' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $branchId = (int) $validated['branch'];
        if ($branchId > 0 && !\App\Models\Branch::whereKey($branchId)->where('status', 1)->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['branch' => 'غير متاح الحجز في الوقت الحالي']);
        }
        $homeCity = $branchId === 0 ? app(\App\Services\HomeService::class)->quote($validated) : null;
        $createdBookingIds = [];

        // Expired unpaid carts must not keep otherwise available appointments blocked.
        app(CartExpirationService::class)->clearExpired();

        DB::transaction(function () use ($validated, $user, $branchId, $homeCity, &$createdBookingIds) {
            foreach ($validated['services'] as $serviceGroup) {
                foreach ($serviceGroup['subServices'] as $subService) {
                    $service = Service::query()->findOrFail($subService['id']);
                    $staffId = (int) $subService['staffId'];
                    $duration = max(1, (int) ($subService['duration'] ?? $service->duration_min ?? 30));
                    $startDateTime = Carbon::createFromFormat(
                        'Y-m-d H:i',
                        $subService['date'] . ' ' . $subService['time']
                    );
                    $endDateTime = $startDateTime->copy()->addMinutes($duration);

                    /*
                     * Serialise availability checks for this employee. This prevents
                     * two simultaneous requests from both passing the conflict check.
                     */
                    $selectedEmployee = User::role('employee')->whereKey($staffId)->lockForUpdate()->first();
                    if (!$selectedEmployee || !(int) $selectedEmployee->status || (int) $selectedEmployee->is_banned) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['staffId' => 'الموظف المختار موقوف أو غير متاح. اختر موظفاً آخر.']);
                    }
                    if ($branchId === 0 && !(int) $selectedEmployee->show_in_home_booking) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['staffId' => 'الموظف غير متاح للحجز المنزلي.']);
                    }

                    $hasConflict = BookingService::query()
                        ->where('employee_id', $staffId)
                        ->whereHas('booking', function ($query) {
                            $query->whereIn('status', ['pending', 'confirmed', 'check_in']);
                        })
                        ->where('start_date_time', '<', $endDateTime)
                        ->whereRaw(
                            'DATE_ADD(start_date_time, INTERVAL GREATEST(COALESCE(duration_min, 0), 1) MINUTE) > ?',
                            [$startDateTime->format('Y-m-d H:i:s')]
                        )
                        ->exists();

                    if ($hasConflict) {
                        throw new HttpResponseException(response()->json([
                            'success' => false,
                            'message' => 'الموعد المختار لم يعد متاحًا. يرجى اختيار موعد آخر.',
                            'errors' => [
                                'time' => ['الموعد المختار يتعارض مع حجز قائم لهذا الموظف.'],
                            ],
                        ], 409));
                    }

                    $availableSlots = app(\App\Http\Controllers\HomeBookingController::class)->getAvailableTimes(
                        new Request(['Increasing' => $duration]), $subService['date'], $staffId
                    )->getData(true);
                    if (!in_array($subService['time'], $availableSlots, true)) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['time' => 'الموعد خارج وردية الموظف الحالية أو لم يعد متاحاً. اختر موعداً جديداً.']);
                    }

                    $booking = new Booking();

                    if ($branchId !== 0) {
                        $booking->note = 'Customer: ' . ($user->first_name ?? '') .
                            ', Mobile: ' . ($user->mobile ?? '') .
                            ', Service: ' . $subService['id'];
                    } else {
                        $booking->note = 'Customer Name: ' . ($validated['customerName'] ?? '') .
                            ', Customer Mobile: ' . ($validated['mobileNo'] ?? '') .
                            ', Neighborhood: ' . ($validated['neighborhood'] ?? '');
                    }

                    $booking->start_date_time = $startDateTime;
                    $booking->user_id = $user->id;
                    $booking->branch_id = $branchId ?: 1;
                    $booking->created_by = $user->id;
                    $booking->status = 'pending';
                    if ($homeCity) {
                        $booking->home_city_id = $homeCity->id;
                        $booking->home_latitude = $validated['latitude'];
                        $booking->home_longitude = $validated['longitude'];
                        $booking->home_service_fee = count($createdBookingIds) === 0 ? $homeCity->home_service_fee : 0;
                        $booking->note .= ', City: ' . $homeCity->name . ', Google Maps: https://maps.google.com/?q=' . $validated['latitude'] . ',' . $validated['longitude'];
                    }
                    $booking->payment_type = 'cart';
                    $booking->save();

                    $bookingService = new BookingService();
                    $bookingService->booking_id = $booking->id;
                    $bookingService->service_id = $subService['id'];
                    $bookingService->employee_id = $staffId;
                    $bookingService->start_date_time = $startDateTime;
                    $branchPrice = $branchId > 0
                        ? $service->branches()->where('branch_id', $branchId)->value('service_price')
                        : null;
                    $freeRewardId = (int) ($subService['repeat_reward_id'] ?? 0);
                    if ($freeRewardId) app(\App\Services\ServiceRepeatRewards::class)->reserve((int)$user->id, (int)$service->id, $freeRewardId, $booking);
                    $bookingService->service_price = ($freeRewardId ? 0 : (float) ($branchPrice ?? $service->default_price ?? 0)) + (float) $booking->home_service_fee;
                    $bookingService->duration_min = $duration;
                    $bookingService->sequance = 1;
                    $bookingService->created_by = $user->id;
                    $bookingService->save();

                    $createdBookingIds[] = $booking->id;
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => __('messages.booking_added_to_cart'),
            'data' => [
                'booking_ids' => $createdBookingIds,
                'count' => count($createdBookingIds),
            ],
        ], 201);
    }

    /*public function storeGiftCard(Request $request)
    {
        if ($request->filled('services') || $request->filled('packages') || $request->filled('location')) {
            $validated = $request->validate([
                'services' => ['nullable', 'array'],
                'services.*.subServices' => ['required_with:services', 'array', 'min:1'],
                'services.*.subServices.*.id' => ['required', 'integer', 'exists:services,id'],
            'services.*.subServices.*.repeat_reward_id' => ['nullable', 'integer', 'min:1'],
                'packages' => ['nullable', 'array'],
                'packages.*.id' => ['required', 'integer', 'exists:packages,id'],
                'location' => ['required', 'array'],
                'location.recipient_name' => ['required', 'string', 'max:255'],
                'location.recipient_mobile' => ['required', 'string', 'max:20'],
                'location.message' => ['nullable', 'string', 'max:1000'],
                'branch' => ['nullable', 'integer'],
                'branch_id' => ['nullable', 'integer'],
            ]);

            $serviceIds = [];

            foreach ($validated['services'] ?? [] as $service) {
                foreach ($service['subServices'] as $subService) {
                    $serviceIds[] = (int) $subService['id'];
                }
            }

            $serviceIds = array_values(array_unique($serviceIds));

            $packageIds = collect($validated['packages'] ?? [])
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            if ($serviceIds === [] && $packageIds === []) {
                return response()->json([
                    'success' => false,
                    'status' => false,
                    'message' => __('messages.gift_card_service_required'),
                ], 422);
            }

            $subtotal = (float) Service::whereIn('id', $serviceIds)
                ->where('status', 1)
                ->sum('default_price');

            $subtotal += (float) Package::whereIn('id', $packageIds)
                ->where('status', 1)
                ->sum('package_price');

            $giftCard = GiftCard::create([
                'user_id' => $request->user()->id,
                'branch_id' => (int) ($validated['branch_id'] ?? $validated['branch'] ?? 0) ?: null,
                'recipient_name' => $validated['location']['recipient_name'],
                'recipient_phone' => $validated['location']['recipient_mobile'],
                'requested_services' => $serviceIds,
                'requested_packages' => $packageIds,
                'message' => $validated['location']['message'] ?? null,
                'subtotal' => $subtotal,
                'payment_status' => 0,
                'gift_status' => GiftCard::STATUS_PENDING_PAYMENT,
            ]);

            return response()->json([
                'success' => true,
                'status' => true,
                'message' => __('messages.gift_added_success'),
                'data' => [
                    'gift_card_id' => $giftCard->id,
                    'subtotal' => (float) $giftCard->subtotal,
                    'claim_url' => $giftCard->claim_url,
                ],
            ], 201);
        }

        $validated = $request->validate([
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_phone' => ['required', 'string', 'max:20'],
            'requested_services' => ['required', 'array', 'min:1'],
            'requested_services.*' => ['integer', 'exists:services,id'],
            'optional_services' => ['nullable', 'string', 'max:100'],
            'branch_id' => ['nullable', 'integer'],
        ]);

        $subtotal = (float) Service::whereIn('id', array_map('intval', $validated['requested_services']))->sum('default_price');

        $giftCard = GiftCard::create([
            'user_id' => $request->user()->id,
            'branch_id' => (int) ($validated['branch_id'] ?? 0) ?: null,
            'recipient_name' => $validated['recipient_name'],
            'recipient_phone' => $validated['recipient_phone'],
            'message' => $validated['optional_services'] ?? null,
            'requested_services' => $validated['requested_services'],
            'subtotal' => $subtotal,
            'payment_status' => 0,
            'gift_status' => GiftCard::STATUS_PENDING_PAYMENT,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('messages.gift_added_success'),
            'data' => [
                'gift_card_id' => $giftCard->id,
                'subtotal' => (float) $giftCard->subtotal,
                'claim_url' => $giftCard->claim_url,
            ],
        ], 201);
    }*/

    public function storeGiftCard(Request $request)
    {
        if ($request->filled('services') || $request->filled('packages') || $request->filled('location')) {
            $validated = $request->validate([
                'services' => ['nullable', 'array'],
                'services.*.subServices' => ['required_with:services', 'array', 'min:1'],
                'services.*.subServices.*.id' => ['required', 'integer', 'exists:services,id'],
            'services.*.subServices.*.repeat_reward_id' => ['nullable', 'integer', 'min:1'],
                'packages' => ['nullable', 'array'],
                'packages.*.id' => ['required', 'integer', 'exists:packages,id'],
                'location' => ['required', 'array'],
                'location.recipient_name' => ['required', 'string', 'max:255'],
                'location.recipient_mobile' => ['required', 'string', 'max:20'],
                'location.message' => ['nullable', 'string', 'max:1000'],
                'location.sender_name' => ['nullable', 'string', 'max:255'],
                'design' => ['nullable', 'string', 'max:50'],
                'type' => ['nullable', 'in:service,package,value'],
                'value_amount' => ['required_if:type,value', 'nullable', 'numeric'],
                'send_channel' => ['nullable', 'in:wa,sms,link,mail,print'],
                'branch' => ['nullable', 'integer'],
                'branch_id' => ['nullable', 'integer'],
            ]);

            $design = \App\Models\GiftDesign::active()->where('slug', $validated['design'] ?? '')->exists()
                ? $validated['design']
                : 'lux-dark';

            // Value gift card: a stored amount instead of services/packages.
            if (($validated['type'] ?? null) === GiftCard::TYPE_VALUE) {
                $giftSettings = app(\App\Services\Gifts\GiftSettings::class);
                $min = $giftSettings->float('gift_value_min');
                $max = $giftSettings->float('gift_value_max');
                $amount = round((float) $validated['value_amount'], 2);
                if ($amount < $min || $amount > $max) {
                    return response()->json([
                        'success' => false,
                        'status' => false,
                        'message' => __('gift_system.value_range', ['min' => $min, 'max' => $max]),
                    ], 422);
                }

                $giftCard = GiftCard::create([
                    'user_id' => $request->user()->id,
                    'type' => GiftCard::TYPE_VALUE,
                    'source' => 'customer',
                    'recipient_name' => $validated['location']['recipient_name'],
                    'recipient_phone' => $validated['location']['recipient_mobile'],
                    'sender_name' => $validated['location']['sender_name'] ?? null,
                    'design' => $design,
                    'requested_services' => [],
                    'requested_packages' => [],
                    'message' => $validated['location']['message'] ?? null,
                    'value_amount' => $amount,
                    'subtotal' => $amount,
                    'payment_status' => 0,
                    'gift_status' => GiftCard::STATUS_PENDING_PAYMENT,
                    'send_channel' => $validated['send_channel'] ?? 'link',
                ]);
                $giftCard->ensureClaimToken();
                app(\App\Services\Gifts\GiftCardService::class)->record($giftCard, 'created', $request->user()->id);

                return response()->json([
                    'success' => true,
                    'status' => true,
                    'message' => __('messages.gift_added_success'),
                    'data' => [
                        'gift_card_id' => $giftCard->id,
                        'subtotal' => (float) $giftCard->subtotal,
                        'claim_url' => $giftCard->claim_url,
                        'share_url' => $giftCard->share_url,
                        'claim_token' => $giftCard->claim_token,
                    ],
                ], 201);
            }

            $serviceIds = [];

            foreach ($validated['services'] ?? [] as $service) {
                foreach ($service['subServices'] as $subService) {
                    $serviceIds[] = (int) $subService['id'];
                }
            }

            $serviceIds = array_values(array_unique($serviceIds));

            $packageIds = collect($validated['packages'] ?? [])
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            if ($serviceIds === [] && $packageIds === []) {
                return response()->json([
                    'success' => false,
                    'status' => false,
                    'message' => __('messages.gift_card_service_required'),
                ], 422);
            }

            $subtotal = (float) Service::whereIn('id', $serviceIds)
                ->where('status', 1)
                ->sum('default_price');

            $subtotal += (float) Package::whereIn('id', $packageIds)
                ->where('status', 1)
                ->sum('package_price');

            $giftCard = GiftCard::create([
                'user_id' => $request->user()->id,
                'branch_id' => (int) ($validated['branch_id'] ?? $validated['branch'] ?? 0) ?: null,
                'recipient_name' => $validated['location']['recipient_name'],
                'recipient_phone' => $validated['location']['recipient_mobile'],
                'sender_name' => $validated['location']['sender_name'] ?? null,
                'design' => $design,
                'type' => $serviceIds === [] ? GiftCard::TYPE_PACKAGE : GiftCard::TYPE_SERVICE,
                'source' => 'customer',
                'requested_services' => $serviceIds,
                'requested_packages' => $packageIds,
                'message' => $validated['location']['message'] ?? null,
                'subtotal' => $subtotal,
                'payment_status' => 0,
                'gift_status' => GiftCard::STATUS_PENDING_PAYMENT,
                'send_channel' => $validated['send_channel'] ?? 'link',
        ]);

            $giftCard->ensureClaimToken();
            app(\App\Services\Gifts\GiftCardService::class)->record($giftCard, 'created', $request->user()->id);

            return response()->json([
                'success' => true,
                'status' => true,
                'message' => __('messages.gift_added_success'),
                'data' => [
                    'gift_card_id' => $giftCard->id,
                    'subtotal' => (float) $giftCard->subtotal,
                    'claim_url' => $giftCard->claim_url,
                    'share_url' => $giftCard->share_url,
                    'claim_token' => $giftCard->claim_token,
                ],
            ], 201);
        }

        $validated = $request->validate([
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_phone' => ['required', 'string', 'max:20'],
            'requested_services' => ['required', 'array', 'min:1'],
            'requested_services.*' => ['integer', 'exists:services,id'],
            'optional_services' => ['nullable', 'string', 'max:100'],
            'branch_id' => ['nullable', 'integer'],
            'send_channel' => ['nullable', 'in:wa,sms,link,mail,print'],
        ]);

        $subtotal = (float) Service::whereIn('id', array_map('intval', $validated['requested_services']))->sum('default_price');

        $giftCard = GiftCard::create([
            'user_id' => $request->user()->id,
            'branch_id' => (int) ($validated['branch_id'] ?? 0) ?: null,
            'recipient_name' => $validated['recipient_name'],
            'recipient_phone' => $validated['recipient_phone'],
            'message' => $validated['optional_services'] ?? null,
            'requested_services' => $validated['requested_services'],
            'subtotal' => $subtotal,
            'payment_status' => 0,
            'gift_status' => GiftCard::STATUS_PENDING_PAYMENT,
            'send_channel' => $validated['send_channel'] ?? 'link',
        ]);


        $giftCard->ensureClaimToken();
        return response()->json([
            'success' => true,
            'message' => __('messages.gift_added_success'),
            'data' => [
                'gift_card_id' => $giftCard->id,
                'subtotal' => (float) $giftCard->subtotal,
                'claim_url' => $giftCard->claim_url,
                    'share_url' => $giftCard->share_url,
                    'claim_token' => $giftCard->claim_token,
            ],
        ], 201);
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

    private function formatDate(mixed $value, string $format): ?string
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
}
