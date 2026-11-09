<?php

namespace Modules\Booking\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingService;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServiceEmployee;

class HomeBookingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:view_booking'])->only(['serviceGroups', 'services', 'staff']);
        $this->middleware(['permission:add_booking'])->only('store');
    }

    public function serviceGroups(): JsonResponse
    {
        $groups = DB::table('categories')
            ->where('status', 1)
            ->where('is_visible', 1)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get(['id', 'name'])
            ->map(fn ($group) => [
                'id' => $group->id,
                'name' => $group->name,
                'display_name' => $this->localizedName($group->name),
            ])
            ->values();

        return response()->json([
            'status' => true,
            'data' => $groups,
        ]);
    }

    public function services(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_group_home_id' => ['required', 'integer', 'exists:categories,id'],
        ]);

        $query = Service::query()
            ->where('category_id', $validated['service_group_home_id'])
            ->where('status', 1)
            ->whereNull('deleted_at');

        $homeVisibleServices = (clone $query)
            ->where('is_visible', 1)
            ->orderBy('id')
            ->get();

        $services = $homeVisibleServices->isNotEmpty()
            ? $homeVisibleServices
            : $query->orderBy('id')->get();

        return response()->json([
            'status' => true,
            'data' => $services->map(fn ($service) => [
                'id' => $service->id,
                'name' => $service->name,
                'display_name' => $this->localizedName($service->name),
                'price' => $service->default_price,
                'duration' => $service->duration_min,
                'is_visible' => (int) $service->is_visible,
            ])->values(),
        ]);
    }

    public function staff(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_home_id' => ['required', 'integer', 'exists:services,id'],
        ]);

        $staff = $this->resolveAvailableEmployees((int) $validated['service_home_id']);

        return response()->json([
            'status' => true,
            'data' => $staff->map(fn (User $employee) => [
                'id' => $employee->id,
                'name' => trim((string) $employee->full_name),
                'mobile' => $employee->mobile,
            ])->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'mobile_no' => ['required', 'string', 'max:20'],
            'city_id' => ['required', 'integer'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'address' => ['required', 'string', 'max:1000'],
            'date' => ['required', 'date'],
            'time' => ['required', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'service_group_home_id' => ['required', 'integer', 'exists:categories,id'],
            'service_home_id' => ['required', 'integer', 'exists:services,id'],
            'staff_home_id' => ['required', 'integer'],
            'notes' => ['nullable', 'string'],
        ]);

        $service = Service::query()
            ->whereKey($validated['service_home_id'])
            ->where('category_id', $validated['service_group_home_id'])
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->first();

        if (! $service) {
            throw ValidationException::withMessages([
                'service_home_id' => ['الخدمة المختارة غير متاحة ضمن هذا القسم.'],
            ]);
        }

        $homeCity = app(\App\Services\HomeService::class)->quote($validated);
        $availableEmployees = $this->resolveAvailableEmployees((int) $service->id);
        $employee = $availableEmployees->firstWhere('id', (int) $validated['staff_home_id']);

        if (! $employee) {
            throw ValidationException::withMessages([
                'staff_home_id' => ['الموظف المختار غير متاح لهذه الخدمة المنزلية.'],
            ]);
        }

        $branchId = (int) (optional($employee->branch)->branch_id ?? 0);
        if ($branchId <= 0) {
            throw ValidationException::withMessages([
                'staff_home_id' => ['الموظف المختار غير مربوط بأي فرع، لذلك لا يمكن إنشاء الحجز.'],
            ]);
        }

        $startDateTime = Carbon::createFromFormat('Y-m-d H:i', $validated['date'] . ' ' . $validated['time']);
        $defaultDuration = max(1, (int) ($service->duration_min ?? 30));
        $duration = $defaultDuration;

        if (! empty($validated['end_time'])) {
            $endDateTime = Carbon::createFromFormat('Y-m-d H:i', $validated['date'] . ' ' . $validated['end_time']);
            $duration = (int) $startDateTime->diffInMinutes($endDateTime, false);

            if ($duration <= 0) {
                throw ValidationException::withMessages([
                    'end_time' => ['وقت النهاية يجب أن يكون بعد وقت البداية.'],
                ]);
            }
        }

        $this->ensureTimeSlotIsAvailable((int) $employee->id, $startDateTime, $duration);

        $customer = $this->findOrCreateCustomer(
            customerName: (string) $validated['customer_name'],
            mobile: (string) $validated['mobile_no']
        );

        $booking = Booking::create([
            'note' => $this->buildBookingNote(
                customerName: (string) $validated['customer_name'],
                mobile: (string) $validated['mobile_no'],
                address: (string) $validated['address'],
                notes: $validated['notes'] ?? null,
                serviceName: $this->localizedName((string) $service->name)
            ),
            'status' => 'confirmed',
            'payment_status' => 0,
            'start_date_time' => $startDateTime,
            'user_id' => $customer->id,
            'branch_id' => $branchId,
            'location' => $validated['address'],
            'home_city_id' => $homeCity->id,
            'home_latitude' => $validated['latitude'],
            'home_longitude' => $validated['longitude'],
            'home_service_fee' => $homeCity->home_service_fee,
            'created_by' => auth()->id(),
            'payment_type' => 'cart',
        ]);

        BookingService::create([
            'booking_id' => $booking->id,
            'service_id' => $service->id,
            'employee_id' => $employee->id,
            'service_price' => (float) ($service->default_price ?? 0) + (float) $homeCity->home_service_fee,
            'duration_min' => $duration,
            'start_date_time' => $startDateTime,
            'sequance' => 1,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم إنشاء الحجز المنزلي بنجاح.',
            'data' => [
                'booking_id' => $booking->id,
                'customer_id' => $customer->id,
            ],
        ]);
    }

    private function resolveAvailableEmployees(int $serviceId): Collection
    {
        $baseQuery = User::query()
            ->active()
            ->employee()
            ->where('show_in_home_booking', 1)
            ->with('branch')
            ->orderBy('first_name');

        $assignedEmployeeIds = ServiceEmployee::query()
            ->where('service_id', $serviceId)
            ->pluck('employee_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($assignedEmployeeIds->isNotEmpty()) {
            $assignedEmployees = (clone $baseQuery)
                ->whereIn('id', $assignedEmployeeIds->all())
                ->get();

            if ($assignedEmployees->isNotEmpty()) {
                return $assignedEmployees;
            }
        }

        return $baseQuery->get();
    }

    private function ensureTimeSlotIsAvailable(int $employeeId, Carbon $startDateTime, int $duration): void
    {
        $requestedEnd = $startDateTime->copy()->addMinutes(max(1, $duration));

        $existingSlots = BookingService::query()
            ->where('employee_id', $employeeId)
            ->whereDate('start_date_time', $startDateTime->toDateString())
            ->whereHas('booking', function ($query) {
                $query->whereIn('status', ['pending', 'confirmed', 'check_in']);
            })
            ->get(['start_date_time', 'duration_min']);

        foreach ($existingSlots as $slot) {
            $existingStart = Carbon::parse($slot->start_date_time);
            $existingEnd = $existingStart->copy()->addMinutes(max(1, (int) ($slot->duration_min ?? 0)));

            if ($startDateTime < $existingEnd && $requestedEnd > $existingStart) {
                throw ValidationException::withMessages([
                    'time' => ['الموعد المختار متعارض مع حجز آخر لنفس الموظف.'],
                ]);
            }
        }
    }

    private function findOrCreateCustomer(string $customerName, string $mobile): User
    {
        $existingCustomer = User::query()->whereMobileMatches($mobile)->first();
        if ($existingCustomer) {
            return $existingCustomer;
        }

        [$firstName, $lastName] = $this->splitCustomerName($customerName);

        $customer = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'mobile' => $mobile,
            'gender' => 'female',
            'password' => Hash::make(Str::random(16)),
            'email_verified_at' => now(),
            'status' => 1,
        ]);

        $customer->syncRoles(['user']);

        return $customer;
    }

    private function splitCustomerName(string $customerName): array
    {
        $normalizedName = preg_replace('/\s+/', ' ', trim($customerName));
        $parts = array_values(array_filter(explode(' ', $normalizedName)));

        $firstName = $parts[0] ?? 'عميل';
        $lastName = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '.';

        return [$firstName, $lastName];
    }

    private function buildBookingNote(string $customerName, string $mobile, string $address, ?string $notes, string $serviceName): string
    {
        $segments = [
            "اسم العميل: {$customerName}",
            "رقم العميل: {$mobile}",
            "نوع الحجز: منزلي",
            "الخدمة: {$serviceName}",
            "العنوان: {$address}",
        ];

        if (filled($notes)) {
            $segments[] = "ملاحظات: {$notes}";
        }

        return implode(' | ', $segments);
    }

    private function localizedName(?string $value): string
    {
        $stringValue = trim((string) $value);
        if ($stringValue === '') {
            return '-';
        }

        $decoded = json_decode($stringValue, true);
        if (! is_array($decoded)) {
            return $stringValue;
        }

        $locale = app()->getLocale();

        return trim((string) ($decoded[$locale] ?? $decoded['ar'] ?? $decoded['en'] ?? $stringValue));
    }
}
