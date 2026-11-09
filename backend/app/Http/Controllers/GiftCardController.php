<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\GiftCard;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingService;
use Modules\Booking\Models\BookingTransaction;
use Modules\Package\Models\Package;
use Modules\Product\Models\Product;
use Modules\Service\Models\Service as ServiceModel;
use Modules\World\Models\State;

class GiftCardController extends Controller
{
    public function index(Request $request)
    {
        $b = $request->query('branch');
        $States = State::where('status', 1)->get();
        $first_States = State::where('status', 1)->first();
        $suggest = Product::with(['media', 'categories'])
            ->where('status', 1)
            ->where('is_featured', 1)
            ->where('deleted_at', null)
            ->take(4)
            ->get();

        return response()->json([
            'status' => true,
            'data' => [
                'branch' => $b,
                'states' => $States,
                'first_state' => $first_States,
                'suggested_products' => $suggest,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'status' => false,
                'need_login' => true,
                'message' => __('messages.login_required_to_continue'),
            ], 200);
        }

        $validated = $request->validate([
            'services' => ['nullable', 'array'],
            'services.*.subServices' => ['required_with:services', 'array', 'min:1'],
            'services.*.subServices.*.id' => ['required', 'integer', 'exists:services,id'],
            'packages' => ['nullable', 'array'],
            'packages.*.id' => ['required', 'integer', 'exists:packages,id'],
            'branch' => ['nullable', 'integer'],
            'location' => ['required', 'array'],
            'location.recipient_name' => ['required', 'string', 'max:255'],
            'location.recipient_mobile' => ['required', 'string', 'max:20'],
            'location.message' => ['nullable', 'string', 'max:1000'],
        ]);

        $serviceIds = collect($validated['services'] ?? [])
            ->flatMap(fn ($service) => collect($service['subServices'])->pluck('id'))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

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

        $services = ServiceModel::query()
            ->whereIn('id', $serviceIds)
            ->where('status', 1)
            ->get();

        if ($services->count() !== count($serviceIds)) {
            return response()->json([
                'success' => false,
                'status' => false,
                'message' => __('messages.gift_card_validation_error'),
            ], 422);
        }

        $packages = Package::query()
            ->whereIn('id', $packageIds)
            ->where('status', 1)
            ->get();

        if ($packages->count() !== count($packageIds)) {
            return response()->json([
                'success' => false,
                'status' => false,
                'message' => __('messages.gift_card_validation_error'),
            ], 422);
        }

        $subtotal = (float) $services->sum(fn (ServiceModel $service) => (float) ($service->default_price ?? 0));
        $subtotal += (float) $packages->sum(fn (Package $package) => (float) ($package->package_price ?? 0));

        $giftCard = GiftCard::create([
            'user_id' => $user->id,
            'type' => $serviceIds === [] ? GiftCard::TYPE_PACKAGE : GiftCard::TYPE_SERVICE,
            'source' => 'customer',
            'branch_id' => $this->resolveBranchId((int) ($validated['branch'] ?? 0)),
            'recipient_name' => $validated['location']['recipient_name'],
            'recipient_phone' => $this->normalizeSaudiPhone($validated['location']['recipient_mobile']),
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
            'message' => __('messages.booking_added_to_cart'),
            'data' => [
                'gift_card_id' => $giftCard->id,
                'subtotal' => (float) $giftCard->subtotal,
            ],
        ], 201);
    }

    public function claim(string $token)
    {
        $giftCard = GiftCard::query()
            ->with(['user', 'branch'])
            ->where('claim_token', $token)
            ->first();

        if (! $giftCard) {
            abort(404);
        }

        $isReady = (int) $giftCard->payment_status === 1;

        return view('frontend.gift-cards.claim', [
            'giftCard' => $giftCard->loadMissing(['user', 'branch']),
            'services' => $isReady ? $giftCard->services_list : collect(),
            'isReady' => $isReady,
        ]);
    }

    public function accept(string $token)
    {
        $giftCard = GiftCard::query()
            ->where('claim_token', $token)
            ->where('payment_status', 1)
            ->firstOrFail();

        if (! $giftCard->claimed_at) {
            $giftCard->forceFill([
                'claimed_at' => now(),
                'gift_status' => GiftCard::STATUS_CLAIMED,
            ])->save();
        }

        return redirect()
            ->route('gift.claim', ['token' => $token])
            ->with('success', __('messages.gift_card_delivered'));
    }

    public function schedule(Request $request, string $token)
    {
        $giftCard = GiftCard::query()
            ->where('claim_token', $token)
            ->where('payment_status', 1)
            ->firstOrFail();

        if ($giftCard->redeemed_at) {
            return response()->json([
                'status' => false,
                'message' => __('messages.duplicate_payment'),
            ], 422);
        }

        $giftServiceIds = collect($giftCard->requested_services ?? [])
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $validated = $request->validate([
            'appointments' => ['required', 'array', 'size:' . count($giftServiceIds)],
            'appointments.*.service_id' => ['required', 'integer', 'exists:services,id'],
            'appointments.*.employee_id' => ['required', 'integer', 'exists:users,id'],
            'appointments.*.date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'appointments.*.time' => ['required', 'date_format:H:i'],
        ]);

        if (! $giftCard->branch_id) {
            return response()->json([
                'status' => false,
                'message' => 'لا يوجد فرع محفوظ لهذه الهدية. يرجى التواصل مع الإدارة.',
            ], 422);
        }

        $appointmentServiceIds = collect($validated['appointments'])
            ->pluck('service_id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        $expectedServiceIds = collect($giftServiceIds)->sort()->values()->all();

        if ($appointmentServiceIds !== $expectedServiceIds) {
            return response()->json([
                'status' => false,
                'message' => __('messages.gift_card_validation_error'),
            ], 422);
        }

        try {
            $bookingIds = DB::transaction(function () use ($validated, $giftCard) {
                $createdBookingIds = [];

                foreach ($validated['appointments'] as $appointment) {
                    $service = ServiceModel::findOrFail((int) $appointment['service_id']);
                    $employee = User::where('id', (int) $appointment['employee_id'])
                        ->where('status', 1)
                        ->firstOrFail();

                    $employeeProvidesService = DB::table('service_employees')
                        ->where('service_id', $service->id)
                        ->where('employee_id', $employee->id)
                        ->exists();

                    $employeeInBranch = DB::table('branch_employee')
                        ->where('branch_id', (int) $giftCard->branch_id)
                        ->where('employee_id', $employee->id)
                        ->exists();

                    if (! $employeeProvidesService || ! $employeeInBranch) {
                        throw new \RuntimeException(__('messages.gift_card_validation_error'));
                    }

                    $startDateTime = Carbon::createFromFormat('Y-m-d H:i', $appointment['date'] . ' ' . $appointment['time'], 'Asia/Riyadh');

                    $booking = Booking::create([
                        'status' => 'pending',
                        'start_date_time' => $startDateTime,
                        'user_id' => $giftCard->user_id,
                        'branch_id' => (int) $giftCard->branch_id,
                        'note' => 'Gift card #' . $giftCard->id . ' recipient: ' . $giftCard->recipient_name . ' mobile: ' . $giftCard->recipient_phone,
                        'created_by' => $giftCard->user_id,
                        'payment_type' => 'gift_card',
                    ]);

                    BookingService::create([
                        'booking_id' => $booking->id,
                        'service_id' => $service->id,
                        'employee_id' => $employee->id,
                        'start_date_time' => $startDateTime,
                        'service_price' => (float) ($service->default_price ?? 0),
                        'duration_min' => (int) ($service->duration_min ?? 0),
                        'sequance' => 1,
                        'created_by' => $giftCard->user_id,
                    ]);

                    BookingTransaction::create([
                        'booking_id' => $booking->id,
                        'external_transaction_id' => 'GIFT-' . $giftCard->id,
                        'transaction_type' => 'gift_card',
                        'payment_status' => 1,
                        'created_by' => $giftCard->user_id,
                    ]);

                    $createdBookingIds[] = $booking->id;
                }

                $giftCard->forceFill([
                    'booking_ids' => $createdBookingIds,
                    'claimed_at' => $giftCard->claimed_at ?: now(),
                    'redeemed_at' => now(),
                    'gift_status' => GiftCard::STATUS_REDEEMED,
                ])->save();

                return $createdBookingIds;
            });
        } catch (\Throwable $exception) {
            Log::warning('Gift card schedule failed', [
                'gift_card_id' => $giftCard->id,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' => __('messages.booking_added_to_cart'),
            'booking_ids' => $bookingIds,
        ]);
    }

    public function handlePaymentResult(Request $request)
    {
        Log::info('Legacy gift payment result route reached', [
            'query' => $request->query(),
        ]);

        return redirect()->route('cart.page');
    }

    private function normalizeSaudiPhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($digits, '966')) {
            return '0' . substr($digits, 3);
        }

        if (preg_match('/^5[0-9]{8}$/', $digits)) {
            return '0' . $digits;
        }

        return $digits ?: $phone;
    }

    private function resolveBranchId(int $branchId): ?int
    {
        if ($branchId <= 0) {
            return null;
        }

        return Branch::where('id', $branchId)->where('status', 1)->exists() ? $branchId : null;
    }
}
