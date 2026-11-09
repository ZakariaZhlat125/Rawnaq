<?php

namespace Modules\Booking\Http\Controllers\Backend;

use App\Authorizable;
use App\Http\Controllers\Controller;
use App\Models\StaffLeavePeriod;
use App\Models\StaffWorkingHour;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Query\Expression;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Employee\Models\BranchEmployee;
use Modules\Booking\Http\Requests\BookingRequest;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingProduct;
use Modules\Package\Models\BookingPackages;
use Modules\Package\Models\UserPackage;
use Modules\Booking\Models\BookingService;
use Modules\Booking\Models\BookingTransaction;
use Modules\Booking\Trait\BookingTrait;
use Modules\Booking\Trait\PaymentTrait;
use Modules\Booking\Transformers\BookingResource;
use Modules\BussinessHour\Models\BussinessHour;
use Modules\Constant\Models\Constant;
use Modules\Holiday\Models\Holiday;
use Modules\Product\Trait\ProductTrait;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServiceEmployee;
use Modules\Tax\Models\Tax;
use Yajra\DataTables\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use Modules\Promotion\Models\UserCouponRedeem;
use Modules\Package\Models\PackageService;
use Modules\Package\Models\UserPackageRedeem;
use Modules\Package\Models\UserPackageServices;
class BookingsController extends Controller
{
    // use Authorizable;
    use BookingTrait;
    use PaymentTrait;
    use ProductTrait;

    protected string $exportClass = '\App\Exports\BookingsExport';
    protected string $overrideUnavailableSlotPermission = 'booking_override_unavailable_slots';

    /**
     * FIX: Toggle for the working-hours/holiday/leave enforcement added to
     * assertNoScheduleConflicts(). Set to false if admins should be allowed to
     * deliberately create manual/overtime bookings outside an employee's normal
     * schedule.
     */
    protected bool $enforceEmployeeAvailability = true;

    public function __construct()
    {
        // Page Title
        $this->module_title = 'booking.title';

        // module name
        $this->module_name = 'bookings';

        // module icon
        $this->module_icon = 'fa-regular fa-sun';

        view()->share([
            'module_title' => $this->module_title,
            'module_name' => $this->module_name,
            'module_icon' => $this->module_icon,
        ]);
        $this->middleware(['permission:view_booking'])->only([
            'index',
            'index_data',
            'list_view',
            'index_list',
            'services_index_list',
            'trashed',
            'slots',
            'payment_success',
            'export',
            'show',
            'viewInvoice',
            'downloadInvoice',
        ]);
        $this->middleware(['permission:edit_booking'])->only([
            'edit',
            'update',
            'restore',
            'updateStatus',
            'updatePaymentStatus',
            'employee_order',
            'payment',
            'payment_create',
            'booking_payment',
            'booking_payment_update',
            'checkout',
            'stripe_payment',
        ]);
        $this->middleware(['permission:add_booking'])->only(['create', 'store']);
        $this->middleware(['permission:delete_booking'])->only(['destroy', 'bulk_action']);
        $this->middleware(['permission:booking_booking_tableview'])->only('datatable_view');
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $module_action = 'List';

        $statusList = $this->statusList();

        $booking = Booking::find(request()->booking_id);

        $date = $booking->start_date_time ?? date('Y-m-d');
        $global_booking = false;

        return view('booking::backend.bookings.index', compact('module_action', 'statusList', 'date', 'global_booking'));
    }

    public function statusList()
    {
        $booking_status = Constant::getAllConstant()->where('type', 'BOOKING_STATUS');
        $checkout_sequence = $booking_status->where('name', 'check_in')->first()->sequence ?? 0;
        $bookingColors = Constant::getAllConstant()->where('type', 'BOOKING_STATUS_COLOR');
        $statusList = [];
    
        foreach ($booking_status as $value) {
            $isDisabled = false;
    
            if (in_array($value->name, ['cancelled', 'completed'])) {
                $isDisabled = true;
            } elseif ($value->sequence >= $checkout_sequence) {
                $isDisabled = true;
            }
    
            $statusList[$value->name] = [
                'title' => $this->bookingStatusTitle($value->name, $value->value),
                'color_hex' => $bookingColors->where('sub_type', $value->name)->first()->name,
                'is_disabled' => $isDisabled,
            ];
    
            // Add next status if it's not cancelled or completed
            if (!in_array($value->name, ['cancelled', 'completed'])) {
                $nextStatus = $booking_status->where('sequence', $value->sequence + 1)->first();
                if ($nextStatus) {
                    $statusList[$value->name]['next_status'] = $nextStatus->name;
                }
            }
        }
    
        return $statusList;
}

    private function bookingStatusTitle(string $status, ?string $fallback = null): string
    {
        $translationKey = 'booking.status_' . $status;
        $translated = __($translationKey);

        return $translated !== $translationKey
            ? $translated
            : ($fallback ?? ucfirst(str_replace('_', ' ', $status)));
    }

    /**
     * @return Response
     */
public function index_list(Request $request)
{
    $date = $request->date ? Carbon::parse($request->date)->toDateString() : Carbon::today()->toDateString();
    $dateStart = $request->date_start ? Carbon::parse($request->date_start)->toDateString() : $date;
    $dateEnd = $request->date_end ? Carbon::parse($request->date_end)->toDateString() : $dateStart;
    $employeeId  = $request->get('employee_id');
    $employeeIdsParam = $request->get('employee_ids');
    $selectedEmployeeIds = [];
    if (is_array($employeeIdsParam)) {
        $selectedEmployeeIds = $employeeIdsParam;
    } elseif (is_string($employeeIdsParam) && $employeeIdsParam !== '') {
        $selectedEmployeeIds = explode(',', $employeeIdsParam);
    }
    $selectedEmployeeIds = array_values(array_unique(array_filter(array_map('intval', $selectedEmployeeIds))));
    if ($employeeId && empty($selectedEmployeeIds)) {
        $selectedEmployeeIds = [(int) $employeeId];
    }

    $viewer = $request->user();
    $isStaffOnly = $viewer && $viewer->hasRole('employee') && ! $viewer->hasRole('admin') && ! $viewer->hasRole('manager');
    if ($isStaffOnly) {
        $selectedEmployeeIds = [(int) $viewer->id];
    }
    $branchId = (int) $request->get('branch_id', 0);

    // ----------------------------
    // Booking Services
    // ----------------------------
    $data = BookingService::with('booking.user', 'booking.branch', 'employee', 'service.category')
        ->whereHas('booking', function ($q) use ($dateStart, $dateEnd, $branchId) {
            if (!empty($dateStart)) {
                $q->whereDate('start_date_time', '>=', $dateStart)
                    ->whereDate('start_date_time', '<=', $dateEnd);
            }
            if ($branchId > 0) {
                $q->where('branch_id', $branchId);
            }
            $q->where('status', '!=', 'cancelled')
                ->where(function ($sub) {
                    $sub->where('payment_type', '!=', 'cart')
                        ->orWhereHas('transactions', function ($t) {
                            $t->where('payment_status', 1);
                        });
                });
        })
        ->when(! empty($selectedEmployeeIds), function ($q) use ($selectedEmployeeIds) {
            $q->whereIn('employee_id', $selectedEmployeeIds);
        })
        ->get();

    // ----------------------------
    // Booking Packages
    // ----------------------------
    $package = BookingPackages::with('booking.user', 'booking.branch', 'employee', 'services.services.category', 'package')
        ->whereHas('booking', function ($q) use ($dateStart, $dateEnd, $branchId) {
            if (!empty($dateStart)) {
                $q->whereDate('start_date_time', '>=', $dateStart)
                    ->whereDate('start_date_time', '<=', $dateEnd);
            }
            if ($branchId > 0) {
                $q->where('branch_id', $branchId);
            }
            $q->where('status', '!=', 'cancelled')
                ->where(function ($sub) {
                    $sub->where('payment_type', '!=', 'cart')
                        ->orWhereHas('transactions', function ($t) {
                            $t->where('payment_status', 1);
                        });
                });
        })
        ->when(! empty($selectedEmployeeIds), function ($q) use ($selectedEmployeeIds) {
            $q->whereIn('employee_id', $selectedEmployeeIds);
        })
        ->get();

    // ----------------------------
    // Format Services
    // ----------------------------
    $service_updated = [];
    $statusList = $this->statusList();
    foreach ($data as $key => $value) {
        $duration = $value->duration_min;
        $startTime = $value->start_date_time;
        $endTime = Carbon::parse($startTime)->addMinutes($duration);

        $serviceName = $value->service->name ?? '';
        $customerName = $value->booking->user->full_name ?? 'Anonymous';
        $branchName = $branchId === 0 ? ($value->booking->branch->name ?? '') : '';
        $employeeName = $value->employee->full_name ?? '';
        $statusTitle = $statusList[$value->booking->status]['title'] ?? $value->booking->status;
        $statusColor = $statusList[$value->booking->status]['color_hex'] ?? '#BF9456';
        $categoryColor = $this->resolveBookingCalendarColor($value->booking, optional(optional($value->service)->category)->calendar_color);
        $eventColor = $this->resolveCalendarEventColor($value->booking->status, $categoryColor, $statusColor);
        $createdByName = optional($value->booking->createdUser)->full_name ?? default_user_name();

        $service_updated[$key] = [
            'id' => $value->booking_id,
            'start' => customDate($startTime, 'Y-m-d H:i'),
            'end' => customDate($endTime, 'Y-m-d H:i'),
            'resourceId' => $value->employee_id,
            'resourceIds' => [(string) $value->employee_id],
            'title' => $serviceName,
            'titleHTML' => view('booking::backend.bookings.calender.event', compact('serviceName', 'customerName', 'branchName', 'createdByName'))->render(),
            'color' => $eventColor,
            'backgroundColor' => $eventColor,
            'extendedProps' => [
                'booking_id' => $value->booking_id,
                'branch_id' => $value->booking->branch_id,
                'employee_id' => $value->employee_id,
                'branch_name' => $value->booking->branch->name ?? '',
                'customer_name' => $customerName,
                'employee_name' => $employeeName,
                'service_name' => $serviceName,
                'created_by_name' => $createdByName,
                'status' => $value->booking->status,
                'status_title' => $statusTitle,
                'status_color' => $statusColor,
                'category_color' => $categoryColor,
                'is_home_booking' => $this->isHomeBooking($value->booking),
                'duration' => $duration,
                'type' => 'service',
            ],
        ];
    }

    // ----------------------------
    // Format Packages
    // ----------------------------
    $package_updated = [];
    foreach ($package as $key => $value) {
        $duration = $value->services->sum('duration_min');
        $startTime = $value->booking->start_date_time;
        $endTime = Carbon::parse($startTime)->addMinutes($duration);

        $serviceName = $value->package->name ?? '';
        $customerName = $value->booking->user->full_name ?? 'Anonymous';
        $branchName = $branchId === 0 ? ($value->booking->branch->name ?? '') : '';
        $employeeName = $value->employee->full_name ?? '';
        $statusTitle = $statusList[$value->booking->status]['title'] ?? $value->booking->status;
        $statusColor = $statusList[$value->booking->status]['color_hex'] ?? '#BF9456';
        $categoryColor = $this->resolvePackageCategoryColor($value);
        $eventColor = $this->resolveCalendarEventColor($value->booking->status, $categoryColor, $statusColor);
        $createdByName = optional($value->booking->createdUser)->full_name ?? default_user_name();

        $package_updated[$key] = [
            'id' => $value->booking_id,
            'start' => customDate($startTime, 'Y-m-d H:i'),
            'end' => customDate($endTime, 'Y-m-d H:i'),
            'resourceId' => $value->employee_id,
            'resourceIds' => [(string) $value->employee_id],
            'title' => $serviceName,
            'titleHTML' => view('booking::backend.bookings.calender.event', compact('serviceName', 'customerName', 'branchName', 'createdByName'))->render(),
            'color' => $eventColor,
            'backgroundColor' => $eventColor,
            'extendedProps' => [
                'booking_id' => $value->booking_id,
                'branch_id' => $value->booking->branch_id,
                'employee_id' => $value->employee_id,
                'branch_name' => $value->booking->branch->name ?? '',
                'customer_name' => $customerName,
                'employee_name' => $employeeName,
                'service_name' => $serviceName,
                'created_by_name' => $createdByName,
                'status' => $value->booking->status,
                'status_title' => $statusTitle,
                'status_color' => $statusColor,
                'category_color' => $categoryColor,
                'duration' => $duration,
                'type' => 'package',
            ],
        ];
    }

    $updated_data = array_merge($service_updated, $package_updated);

    // ----------------------------
    // Employees
    // ----------------------------
    $orderEmployeesQuery = User::select('users.*')
        ->with('branches', 'media')
        ->active()
        ->varified()
        ->employee()
        ->where('is_manager', 0)
        ->orderBy('id', 'ASC');

    if ($branchId > 0) {
        $orderEmployeesQuery->whereHas('branches', function ($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        });
    }

    if ($isStaffOnly) {
        $orderEmployeesQuery->where('users.id', $viewer->id);
    }

    $preferenceMap = $this->getCalendarEmployeePreferenceMap($viewer, $branchId);
    $orderEmployees = $this->sortCalendarEmployees($orderEmployeesQuery->get(), $branchId, $viewer, $preferenceMap);
    $employees = $orderEmployees
        ->filter(fn ($employee) => $this->isCalendarEmployeeVisibleForUser($employee, $branchId, $viewer, $preferenceMap))
        ->values();

    if ($isStaffOnly) {
        $orderEmployees = $orderEmployees->where('id', $viewer->id)->values();
        $employees = $orderEmployees;
    } elseif (! empty($selectedEmployeeIds)) {
        $employees = $orderEmployees->whereIn('id', $selectedEmployeeIds)->values();
    }

    $employeeIds = $employees->pluck('id')->all();

    // FIX: Previously booking events were filtered down to $employeeIds, which is the
    // visibility/selection-filtered employee list. That meant a booking's event would
    // silently vanish from the calendar the moment its assigned employee was hidden via
    // the per-user "calendar_employee_preferences" toggle, or whenever a specific
    // employee filter was active and didn't include that booking's employee. Booking
    // *data* should not depend on a display preference. We now only require the
    // employee to belong to the current branch roster at all (regardless of their
    // visibility toggle), so the event isn't dropped just because a column is hidden.
    $allBranchEmployeeIds = $orderEmployees->pluck('id')->all();
    $visibleBookingEvents = array_values(array_filter($updated_data, function ($event) use ($allBranchEmployeeIds) {
        return in_array((int) ($event['resourceId'] ?? 0), $allBranchEmployeeIds, true);
    }));
    $employeeBranchIds = $employees->mapWithKeys(function ($employee) use ($branchId) {
        return [$employee->id => $this->effectiveEmployeeBranchId($employee, $branchId)];
    });
    $branchIds = $employeeBranchIds->filter()->unique()->values()->all();
    $availabilityDates = collect();
    for ($cursor = Carbon::parse($dateStart); $cursor->lte(Carbon::parse($dateEnd)); $cursor->addDay()) {
        $availabilityDates->push($cursor->toDateString());
    }

    $resource = [];
    $orderResource = [];
    foreach ($orderEmployees as $employee) {
        $employeeBranchId = $this->effectiveEmployeeBranchId($employee, $branchId);
        $orderResource[] = [
            'id' => $employee->id,
            'branch_id' => $employeeBranchId,
            'is_visible' => $this->isCalendarEmployeeVisibleForUser($employee, $branchId, $viewer, $preferenceMap),
            'title' => $employee->full_name,
        ];
    }

    $availabilityEvents = [];
    $availability = [];
    foreach ($employees as $employee) {
        $employeeBranchId = (int) $employeeBranchIds->get($employee->id);
        $resource[] = [
            'id' => $employee->id,
            'branch_id' => $employeeBranchId,
            'extendedProps' => [
                'branch_id' => $employeeBranchId,
            ],
            'title' => $employee->full_name,
            'titleHTML' => '<div class="d-flex gap-3 justify-content-center align-items-center py-3">
                <img src="' . $employee->profile_image . '" class="avatar avatar-40 rounded-pill" alt="employee" />
                ' . $employee->full_name . '
            </div>',
        ];

        $availability[$employee->id] = [];
        foreach ($availabilityDates as $availabilityDate) {
            $availabilityContext = $this->availabilityContextForDate($availabilityDate, $branchIds, $employeeIds);
            $employeeAvailability = $this->buildEmployeeAvailability($employee, $availabilityDate, $employeeBranchId, $availabilityContext);
            $availability[$employee->id] = array_merge($availability[$employee->id], $employeeAvailability['ranges']);
            $availabilityEvents = array_merge($availabilityEvents, $employeeAvailability['events']);
        }
    }

    return response()->json([
        'data' => array_merge($availabilityEvents, $visibleBookingEvents),
        'employees' => $resource,
        'order_employees' => $orderResource,
        'availability' => $availability,
        'total_count' => count($employees),
    ]);
}

    public function updateEmployeeOrder(Request $request)
    {
        if (! $request->user()?->can('view_booking')) {
            return response()->json([
                'status' => false,
                'message' => __('messages.permission_denied'),
            ], 403);
        }

        $validated = $request->validate([
            'branch_id' => ['nullable', 'integer'],
            'employees' => ['required', 'array', 'min:1'],
            'employees.*.id' => ['required', 'integer', 'distinct'],
            'employees.*.is_visible' => ['required', 'boolean'],
        ]);

        $branchId = (int) ($validated['branch_id'] ?? 0);
        $employeeRows = array_values($validated['employees']);
        $employeeIds = array_map(fn ($employee) => (int) $employee['id'], $employeeRows);

        $employeesQuery = User::with('branches')
            ->active()
            ->varified()
            ->employee()
            ->where('is_manager', 0)
            ->whereIn('id', $employeeIds);

        if ($branchId > 0) {
            $employeesQuery->whereHas('branches', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        $employees = $employeesQuery->get()->keyBy('id');
        $preferences = [];

        foreach ($employeeRows as $index => $employeeRow) {
            $employeeId = (int) $employeeRow['id'];
            if (! $employees->has($employeeId)) {
                continue;
            }

            $preferences[] = [
                'id' => $employeeId,
                'is_visible' => (bool) $employeeRow['is_visible'],
                'order' => $index + 1,
            ];
        }

        $this->storeCalendarEmployeePreferences($request->user(), $branchId, $preferences);

        return response()->json([
            'status' => true,
            'message' => __('Employee order updated successfully.'),
        ]);
    }

    private function sortCalendarEmployees($employees, int $branchId, ?User $viewer = null, array $preferenceMap = [])
    {
        if (empty($preferenceMap)) {
            $preferenceMap = $this->getCalendarEmployeePreferenceMap($viewer, $branchId);
        }

        return $employees->sort(function ($first, $second) use ($branchId, $preferenceMap) {
            $firstBranch = $branchId > 0
                ? $first->branches->firstWhere('branch_id', $branchId)
                : $first->branches->first();
            $secondBranch = $branchId > 0
                ? $second->branches->firstWhere('branch_id', $branchId)
                : $second->branches->first();

            $firstOrder = $preferenceMap[(int) $first->id]['order'] ?? ($firstBranch?->calendar_sort_order ?? PHP_INT_MAX);
            $secondOrder = $preferenceMap[(int) $second->id]['order'] ?? ($secondBranch?->calendar_sort_order ?? PHP_INT_MAX);

            return ($firstOrder <=> $secondOrder) ?: ((int) $first->id <=> (int) $second->id);
        })->values();
    }

    private function getCalendarEmployeePreferenceScope(int $branchId): string
    {
        return 'branch_' . ($branchId > 0 ? $branchId : 'all');
    }

    private function getCalendarEmployeePreferenceMap(?User $viewer, int $branchId): array
    {
        if (! $viewer) {
            return [];
        }

        $settings = is_array($viewer->user_setting) ? $viewer->user_setting : [];
        $scope = $this->getCalendarEmployeePreferenceScope($branchId);
        $rows = data_get($settings, "calendar_employee_preferences.{$scope}", []);
        $map = [];

        foreach ((array) $rows as $index => $row) {
            $employeeId = (int) data_get($row, 'id');
            if ($employeeId <= 0) {
                continue;
            }

            $map[$employeeId] = [
                'order' => (int) (data_get($row, 'order') ?: ($index + 1)),
                'is_visible' => filter_var(data_get($row, 'is_visible', true), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ];
        }

        return $map;
    }

    private function isCalendarEmployeeVisibleForUser(User $employee, int $branchId, ?User $viewer = null, array $preferenceMap = []): bool
    {
        if (empty($preferenceMap)) {
            $preferenceMap = $this->getCalendarEmployeePreferenceMap($viewer, $branchId);
        }

        $preference = $preferenceMap[(int) $employee->id] ?? null;

        if (is_array($preference) && array_key_exists('is_visible', $preference) && $preference['is_visible'] !== null) {
            return (bool) $preference['is_visible'];
        }

        return (bool) $employee->show_in_calender;
    }

    private function storeCalendarEmployeePreferences(User $viewer, int $branchId, array $preferences): void
    {
        $settings = is_array($viewer->user_setting) ? $viewer->user_setting : [];
        $calendarPreferences = (array) data_get($settings, 'calendar_employee_preferences', []);
        $scope = $this->getCalendarEmployeePreferenceScope($branchId);

        $calendarPreferences[$scope] = array_values(array_map(function ($row) {
            return [
                'id' => (int) data_get($row, 'id'),
                'is_visible' => (bool) data_get($row, 'is_visible', true),
                'order' => (int) data_get($row, 'order', 0),
            ];
        }, $preferences));

        $settings['calendar_employee_preferences'] = $calendarPreferences;

        $viewer->forceFill([
            'user_setting' => $settings,
        ])->save();
    }

    private function effectiveEmployeeBranchId(User $employee, int $branchId): int
    {
        if ($branchId > 0) {
            return $branchId;
        }

        return (int) optional($employee->branches->first())->branch_id;
    }

    private function normalizeCalendarColor(?string $color): ?string
    {
        $color = trim((string) $color);

        if ($color === '') {
            return null;
        }

        if ($color[0] !== '#') {
            $color = '#' . $color;
        }

        return preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $color) ? strtoupper($color) : null;
    }

    private function isHomeBooking(?Booking $booking): bool
    {
        if (! $booking) {
            return false;
        }

        return str_contains((string) $booking->note, 'نوع الحجز: منزلي');
    }

    private function resolveBookingCalendarColor(?Booking $booking, ?string $fallbackCategoryColor = null): ?string
    {
        if ($this->isHomeBooking($booking)) {
            $homeBookingColor = $this->normalizeCalendarColor((string) setting('home_booking_calendar_color', '#0EA5E9'));

            if ($homeBookingColor) {
                return $homeBookingColor;
            }
        }

        return $this->normalizeCalendarColor($fallbackCategoryColor);
    }

    private function resolvePackageCategoryColor(BookingPackages $bookingPackage): ?string
    {
        foreach ($bookingPackage->services ?? [] as $packageService) {
            $color = $this->normalizeCalendarColor(optional(optional($packageService->services)->category)->calendar_color);

            if ($color) {
                return $color;
            }
        }

        return null;
    }

    private function resolveCalendarEventColor(?string $status, ?string $categoryColor, ?string $statusColor): string
    {
        if ($status === 'check_in') {
            return '#6B7280';
        }

        return $categoryColor ?: ($statusColor ?: '#BF9456');
    }

    private function availabilityContextForDate(string $date, array $branchIds, array $employeeIds): array
    {
        $dayName = strtolower(Carbon::parse($date)->format('l'));

        return [
            'day_name' => $dayName,
            'holiday_branch_ids' => Holiday::whereIn('branch_id', $branchIds)
                ->whereDate('date', $date)
                ->pluck('branch_id')
                ->mapWithKeys(fn ($id) => [(int) $id => true])
                ->all(),
            'leave_staff_ids' => StaffLeavePeriod::whereIn('staff_id', $employeeIds)
                ->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->pluck('staff_id')
                ->mapWithKeys(fn ($id) => [(int) $id => true])
                ->all(),
            'staff_working_hours' => StaffWorkingHour::whereIn('staff_id', $employeeIds)
                ->where('day_of_week', $dayName)
                ->orderBy('id', 'desc')
                ->get()
                ->unique('staff_id')
                ->keyBy('staff_id'),
            'business_hours' => BussinessHour::whereIn('branch_id', $branchIds)
                ->where('day', $dayName)
                ->where('is_holiday', 0)
                ->orderBy('id', 'desc')
                ->get(),
        ];
    }

    private function buildEmployeeAvailability(User $employee, string $date, int $branchId, array $context): array
    {
        $resourceId = $employee->id;
        $resourceIds = [(string) $resourceId];
        $dayName = $context['day_name'];
        $dayStart = "{$date} 00:00:00";
        $dayEnd = "{$date} 23:59:59";

        $events = [
            [
                'id' => "staff-unavailable-{$resourceId}-{$date}",
                'start' => $dayStart,
                'end' => $dayEnd,
                'resourceId' => $resourceId,
                'resourceIds' => $resourceIds,
                'display' => 'background',
                'backgroundColor' => '#e5e7eb',
                'color' => '#e5e7eb',
                'editable' => false,
            ],
        ];

        if (Carbon::parse($date)->lt(Carbon::today())) {
            return ['events' => $events, 'ranges' => []];
        }

        if ($branchId > 0 && isset($context['holiday_branch_ids'][$branchId])) {
            return ['events' => $events, 'ranges' => []];
        }

        if (isset($context['leave_staff_ids'][(int) $resourceId])) {
            return ['events' => $events, 'ranges' => []];
        }

        $workingConfig = $this->resolveEmployeeWorkingConfig($employee, $branchId, $dayName, $context);
        if (!$workingConfig) {
            return ['events' => $events, 'ranges' => []];
        }

        $availableStart = "{$date} {$workingConfig['start_time']}";
        $availableEnd = "{$date} {$workingConfig['end_time']}";
        if (Carbon::parse($availableEnd)->lessThanOrEqualTo(Carbon::parse($availableStart))) {
            return ['events' => $events, 'ranges' => []];
        }

        $events[] = [
            'id' => "staff-available-{$resourceId}-{$date}",
            'start' => $availableStart,
            'end' => $availableEnd,
            'resourceId' => $resourceId,
            'resourceIds' => $resourceIds,
            'display' => 'background',
            'backgroundColor' => '#ffffff',
            'color' => '#ffffff',
            'editable' => false,
        ];

        $breakRanges = [];
        foreach ($workingConfig['breaks'] as $index => $break) {
            if (empty($break['start_break']) || empty($break['end_break'])) {
                continue;
            }

            $breakStart = "{$date} {$this->normalizeTime($break['start_break'])}";
            $breakEnd = "{$date} {$this->normalizeTime($break['end_break'])}";
            if (Carbon::parse($breakEnd)->lessThanOrEqualTo(Carbon::parse($breakStart))) {
                continue;
            }

            $breakRanges[] = [
                'start' => $breakStart,
                'end' => $breakEnd,
            ];

            $events[] = [
                'id' => "staff-break-{$resourceId}-{$date}-{$index}",
                'start' => $breakStart,
                'end' => $breakEnd,
                'resourceId' => $resourceId,
                'resourceIds' => $resourceIds,
                'display' => 'background',
                'backgroundColor' => '#e5e7eb',
                'color' => '#e5e7eb',
                'editable' => false,
            ];
        }

        return [
            'events' => $events,
            'ranges' => [
                [
                    'start' => $availableStart,
                    'end' => $availableEnd,
                    'breaks' => $breakRanges,
                ],
            ],
        ];
    }

    private function resolveEmployeeWorkingConfig(User $employee, int $branchId, string $dayName, array $context): ?array
    {
        $staffWorkingHours = $context['staff_working_hours']->get($employee->id);

        if ($staffWorkingHours) {
            if ($staffWorkingHours->is_holiday) {
                return null;
            }

            return [
                'start_time' => $this->normalizeTime($staffWorkingHours->start_time),
                'end_time' => $this->normalizeTime($staffWorkingHours->end_time),
                'breaks' => $this->normalizeBreaks($staffWorkingHours->breaks),
            ];
        }

        $branchEmployee = $employee->branches->firstWhere('branch_id', $branchId);
        $shiftId = $branchEmployee->shift_id ?? null;

        $workingHours = $context['business_hours']->first(function ($hours) use ($branchId, $shiftId) {
            if ((int) $hours->branch_id !== (int) $branchId) {
                return false;
            }

            return $shiftId ? (int) $hours->shift_id === (int) $shiftId : true;
        });

        if (!$workingHours) {
            return null;
        }

        return [
            'start_time' => $this->normalizeTime($workingHours->start_time),
            'end_time' => $this->normalizeTime($workingHours->end_time),
            'breaks' => $this->normalizeBreaks($workingHours->breaks),
        ];
    }

    private function normalizeBreaks($breaks): array
    {
        if (is_string($breaks)) {
            $breaks = json_decode($breaks, true);
        }

        return is_array($breaks) ? $breaks : [];
    }

    private function normalizeTime(?string $time): string
    {
        return Carbon::parse((string) $time)->format('H:i:s');
    }


    public function services_index_list(Request $request)
    {
        $employee_id = $request->employee_id;
        $branch_id = $request->branch_id;
        $locale = app()->getLocale();
        $data = Service::selectRaw("JSON_UNQUOTE(JSON_EXTRACT(services.name, '$.\"$locale\"')) as service_name,service_branches.*")
            // select('services.name as service_name', 'service_branches.*')
            ->with('employee')
            ->leftJoin('service_branches', 'service_branches.service_id', 'services.id')
            ->whereHas('category', function ($q) {
                $q->active();
            })
            ->where('branch_id', $branch_id);

        if (isset($employee_id)) {
            $data = $data->whereHas('employee', function ($q) use ($employee_id) {
                $q->where('employee_id', $employee_id);
            });
        }

        $data = $data->get();

        return response()->json($data);
    }

    public function datatable_view(Request $request)
    {
        $module_action = 'List';

        $filter = [
            'status' => $request->status,
        ];

        $booking_status = Constant::getAllConstant()->where('type', 'BOOKING_STATUS');

        $export_import = true;
        $export_columns = [
            [
                'value' => 'date',
                'text' => 'Date',
            ],
            [
                'value' => 'customer',
                'text' => 'Customer Name',
            ],
            [
                'value' => 'service_amount',
                'text' => 'Amount',
            ],
            [
                'value' => 'service_duration',
                'text' => 'Duration',
            ],
            [
                'value' => 'employee',
                'text' => 'Staff Name',
            ],
            [
                'value' => 'change_staff',
                'text' => 'Change Staff',
            ],
            [
                'value' => 'services',
                'text' => 'Services',
            ],
            [
                'value' => 'status',
                'text' => 'Status',
            ],
            [
                'value' => 'updated_at',
                'text' => 'Updated At',
            ],
        ];
        $export_url = route('backend.bookings.export');

        return view('booking::backend.bookings.index_datatable', compact('module_action', 'filter', 'booking_status', 'export_import', 'export_columns', 'export_url'));
    }

    public function index_data(Datatables $datatable, Request $request)
    {
        $module_name = $this->module_name;

        $query = Booking::with('branch', 'user', 'services', 'mainServices', 'payment', 'bookingPackages', 'bookedPackageService', 'userPackageServices')
            ->where(function ($q) {
                $q->where('payment_type', '!=', 'cart')
                    ->orWhereHas('transactions', function ($t) {
                        $t->where('payment_status', 1);
                    });
            });

        $viewer = auth()->user();
        if ($viewer && $viewer->hasRole('employee') && ! $viewer->hasRole('admin') && ! $viewer->hasRole('manager')) {
            $query->where(function ($q) use ($viewer) {
                $q->whereHas('services', fn ($s) => $s->where('employee_id', $viewer->id))
                  ->orWhereHas('bookingPackages', fn ($p) => $p->where('employee_id', $viewer->id));
            });
        }

        $filter = $request->filter;

        if (isset($filter)) {
            if (isset($filter['column_status'])) {
                $query->where('status', $filter['column_status']);
            }
            if (isset($filter['booking_date'])) {
                try {
                    $startDate = explode(' to ', $filter['booking_date'])[0];
                    $endDate = explode(' to ', $filter['booking_date'])[1];
                    $query->whereDate('start_date_time', '>=', $startDate);
                    $query->whereDate('start_date_time', '<=', $endDate);
                } catch (\Exception $e) {
                    \Log::error($e->getMessage());
                }
            }
            if (isset($filter['user_id'])) {
                $query->where('user_id', $filter['user_id']);
            }
            if (isset($filter['emploee_id'])) {
                $query->whereHas('services', function ($q) use ($filter) {
                    $q->where('employee_id', $filter['emploee_id']);
                });
            }
            if (isset($filter['service_id'])) {
                $query->whereHas('services', function ($q) use ($filter) {
                    $q->whereIn('service_id', $filter['service_id']);
                });
            }
        }

        $booking_status = Constant::getAllConstant()->where('type', 'BOOKING_STATUS');
        $booking_colors = Constant::getAllConstant()->where('type', 'BOOKING_STATUS_COLOR');

        $payment_status = Constant::getAllConstant()->where('type', 'PAYMENT_STATUS')->where('status', '=', '1');

        return $datatable->eloquent($query)
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-' . $row->id . '"  name="datatable_ids[]" value="' . $row->id . '" onclick="dataTableRowCheck(' . $row->id . ')">';
            })
            ->addColumn('action', function ($data) use ($module_name) {
                return view('booking::backend.bookings.datatable.action_column', compact('module_name', 'data'));
            })
            ->editColumn('status', function ($data) use ($booking_status, $booking_colors) {
                return view('booking::backend.bookings.datatable.select_column', compact('data', 'booking_status', 'booking_colors'));
            })
            ->editColumn('payment_status', function ($data) use ($payment_status, $booking_colors) {

                return view('booking::backend.bookings.datatable.select_payment_status', compact('data', 'payment_status', 'booking_colors'));
            })
            ->editColumn('user_id', function ($data) {
                $user = optional($data->user);
                $Profile_image = $user->profile_image ?? default_user_avatar();
                $name = $user->full_name ?? default_user_name();
                $mobile = $user->mobile ?? '--';

                return view('booking::backend.bookings.datatable.user_id', compact('Profile_image', 'name', 'mobile'));
            })
            ->editColumn('employee_id', function ($data) {
                // $Profile_image = $data->services->first()->employee?->profile_image ?? $data->bookingPackages->first()->employee?->profile_image ?? default_user_avatar() ;
                // $name = $data->services->first()->employee?->full_name ?? $data->bookingPackages->first()->employee?->full_name ?? default_user_name();
                // $email = $data->services->first()->employee?->email ?? $data->bookingPackages->first()->employee?->email ?? '--';

                $employee = optional($data->services->first())->employee
                    ?: optional($data->bookingPackages->first())->employee;

                $Profile_image = $employee->profile_image ?? default_user_avatar();
                $name = $employee->full_name ?? default_user_name();
                $mobile = $employee->mobile ?? '--';

                return view('booking::backend.bookings.datatable.employee_id', compact('Profile_image', 'name', 'mobile'));
            })
            ->editColumn('service_amount', function ($data) {
                $serviceAmount = $data->services->sum('service_price');
                if ($data->bookingPackages->isNotEmpty()) {

                    foreach ($data->bookingPackages as $bookingPackage) {
                        if ($bookingPackage->is_reclaim == 0) {
                            $serviceAmount += $bookingPackage->package_price;
                        }
                    }
                }
                return '<span>' . \Currency::format($serviceAmount) . '</span>';
            })
            ->editColumn('service_duration', function ($data) {

                return '<span>' . $data->calculateServiceDuration() . ' Min</span>';

            })
            ->editColumn('services', function ($data) {
                return view('booking::backend.bookings.datatable.services', compact('data'));
            })
            ->editColumn('packages', function ($data) {
                if ($data->bookingPackages->isNotEmpty()) {
                    $packageNames = $data->bookingPackages->pluck('name')->implode(', ');
                    return '<small class="badge bg-primary">' . $packageNames . '</small>';
                }
                return '<span class="badge bg-primary">' . '-' . '</span>';
            })
            ->editColumn('start_date_time', function ($data) {
                return customDate($data->start_date_time);
            })
            ->editColumn('updated_at', function ($data) {
                $diff = timeAgoInt($data->updated_at);

                if ($diff < 25) {
                    return timeAgo($data->updated_at);
                } else {
                    return customDate($data->updated_at);
                }
            })
            ->editColumn('id', function ($row) {
                return "<a href='" . route('backend.bookings.index', ['booking_id' => $row->id]) . "'>$row->id</a>";
            })
            ->orderColumn('service_amount', function ($query, $order) {
                $query->orderBy(new Expression('(SELECT SUM(service_price) FROM booking_services WHERE booking_id = bookings.id)'), $order);
            }, 1)
            ->orderColumn('service_duration', function ($query, $order) {
                $query->orderBy(new Expression('(SELECT SUM(duration_min) FROM booking_services WHERE booking_id = bookings.id)'), $order);
            }, 1)
            ->orderColumn('employee_id', function ($query, $order) {
                $query->select('bookings.*')
                    ->leftJoin('booking_services', 'booking_services.booking_id', '=', 'bookings.id')
                    ->leftJoin('users', 'users.id', '=', 'booking_services.employee_id')
                    ->orderBy('users.first_name', $order);
            }, 1)
            ->filterColumn('services', function ($query, $keyword) {
                $query->whereHas('mainServices', function ($q) use ($keyword) {
                    $q->where('name', 'like', '%' . $keyword . '%');
                });
            })
            ->filterColumn('employee_id', function ($query, $keyword) {
                if (!empty($keyword)) {
                    $query->whereHas('services', function ($q) use ($keyword) {
                        $q->whereHas('employee', function ($qn) use ($keyword) {
                            $qn->where('first_name', 'like', '%' . $keyword . '%');
                            $qn->orWhere('last_name', 'like', '%' . $keyword . '%');
                            $qn->orWhere('mobile', 'like', '%' . $keyword . '%');
                        });
                    });
                }
            })
            ->orderColumn('user_id', function ($query, $order) {
                $query->select('bookings.*')
                    ->leftJoin('users', 'users.id', '=', 'bookings.user_id')
                    ->orderByRaw('CONCAT(users.first_name, " ", users.last_name) ' . $order);
            })
            ->filterColumn('user_id', function ($query, $keyword) {
                if (!empty($keyword)) {
                    $query->whereHas('user', function ($q) use ($keyword) {
                        $q->whereRaw('CONCAT(first_name, " ", last_name) LIKE ?', ['%' . $keyword . '%']);
                        $q->orWhere('mobile', 'like', '%' . $keyword . '%');
                    });
                }
            })
            ->addColumn('change_staff', function ($row) {
                $changed = $row->services->contains(function ($service) {
                    return (int) $service->change_staff === 1;
                });
                return $changed ? 1 : 0; 
            })
            ->rawColumns(['check', 'id', 'action', 'status', 'services', 'service_duration', 'service_amount', 'start_date_time', 'payment_status', 'packages' , 'change_staff'])
            // ->orderColumn('updated_at', 'desc')
            ->orderColumns(['id'], '-:column $1')
            ->make(true);
    }

    /**
     * Store a newly created resource in storage.
     *
     * FIX: Wrapped in DB::transaction() and now verifies the freshly created booking
     * doesn't clash with any other employee's existing schedule before committing. If a
     * conflict is found, a ValidationException is thrown, the transaction rolls back
     * (nothing is persisted), and Laravel automatically returns a 422 JSON response.
     *
     * @return Response
     */
    public function store(BookingRequest $request)
    {
        // FIX: acquire an advisory lock per candidate employee/date *before* writing
        // anything. lockForUpdate() inside the transaction only protects rows that
        // already exist — for a brand-new booking there may be nothing yet to lock, so
        // two simultaneous "book the same free slot" requests could otherwise both pass
        // the conflict check. The advisory lock closes that gap.
        $employeeIds = $this->extractRequestEmployeeIds($request);
        $date = $this->extractRequestDate($request);

        return $this->withEmployeeScheduleLocks($employeeIds, $date, function () use ($request) {
            return DB::transaction(function () use ($request) {
                $bookingData = $request->except(['services_id', 'employee_id', '_token']);

                $bookingData['status'] = 'confirmed';

                $booking = Booking::create($bookingData);

                $this->updateBookingService($request->services, $booking->id);
                $this->updateBookingPackage($request->purchase_packages, $booking->id);
                $this->storeUserPackage($booking->id);

                // FIX: reject the whole booking if any assigned employee is now double-booked,
                // double-booked within this same booking, or scheduled outside their availability.
                $this->assertNoScheduleConflicts($booking);

                $message = __('messages.create_form', ['form' => __('booking.singular_title')]);

                try {
                    $type = 'new_booking';
                    $messageTemplate = 'New booking #[[booking_id]] has been booked.';
                    $notify_message = str_replace('[[booking_id]]', $booking->id, $messageTemplate);
                    $this->sendNotificationOnBookingUpdate($type, $notify_message, $booking);
                } catch (\Exception $e) {
                    \Log::error($e->getMessage());
                }

                $data = Booking::with('services', 'user', 'products', 'packages', 'bookingPackages.services', )->findOrFail($booking->id);

                return response()->json(['message' => $message, 'status' => true, 'data' => new BookingResource($data)], 200);
            }, 3); // FIX: retry up to 3x on deadlock before giving up
        });
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function checkEmployeeBookingAccess(Booking $booking): void
    {
        $viewer = auth()->user();
        if ($viewer && $viewer->hasRole('employee') && ! $viewer->hasRole('admin') && ! $viewer->hasRole('manager')) {
            $isAssigned = $booking->services()->where('employee_id', $viewer->id)->exists()
                || $booking->bookingPackages()->where('employee_id', $viewer->id)->exists();
            abort_unless($isAssigned, 403, 'غير مصرح لك بالوصول لهذا الحجز.');
        }
    }

    public function show($id)
    {
        $booking = Booking::with(['services', 'user', 'products', 'userCouponRedeem'])->find($id);

        if (is_null($booking)) {
            return response()->json(['message' => __('messages.booking_not_found')], 404);
        }

        $this->checkEmployeeBookingAccess($booking);

        $bookingTransaction = BookingTransaction::where('booking_id', $booking->id)->where('payment_status', 1)->first();

        $booking_product = BookingProduct::where('booking_id', $booking->id)->get();

        $sumDiscountedPrice = 0;

        if ($booking_product != '') {
            $sumDiscountedPrice = $booking_product->sum('discounted_price');
        }

        $data = [
            'booking' => new BookingResource($booking),
            'services_total_amount' => $booking->services->sum('service_price'),
            'booking_transaction' => $bookingTransaction,
            'product_amount' => $sumDiscountedPrice,
            'package_amount' => $booking->packages->sum('package_price'),
            'coupon_discount' => $booking->userCouponRedeem->discount ?? 0
        ];
        return response()->json(['status' => true, 'data' => $data]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        $data = Booking::with([
            'services',
            'user',
            'products',
            'packages',
            'bookingPackages.services',
            'userCouponRedeem'  // Ensure this is included
        ])->findOrFail($id);

        $this->checkEmployeeBookingAccess($data);

        return response()->json(['data' => new BookingResource($data), 'status' => true]);
    }


    /**
     * Update the specified resource in storage.
     *
     * FIX: Wrapped in DB::transaction() (with the booking row locked via
     * lockForUpdate()) so two concurrent reschedule/edit requests for the same booking
     * can't race each other, and every branch now runs assertNoScheduleConflicts()
     * before the response is built.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(BookingRequest $request, $id)
    {
        $existing = Booking::with('services', 'bookingPackages')->findOrFail($id);
        $this->checkEmployeeBookingAccess($existing);
        $fingerprintBefore = app(\App\Services\BookingWhatsAppService::class)->fingerprint($existing);

        // FIX: lock both the employees currently assigned to this booking AND any new
        // employee(s) named in the request — a reschedule can move a booking onto an
        // employee who wasn't previously involved, and that employee's schedule needs
        // the same protection as a brand-new booking would get.
        $employeeIds = array_merge(
            $this->extractRequestEmployeeIds($request),
            $existing ? $existing->services->pluck('employee_id')->all() : [],
            $existing ? $existing->bookingPackages->pluck('employee_id')->all() : []
        );
        $date = $this->extractRequestDate($request, $existing->start_date_time ?? null);

        $response = $this->withEmployeeScheduleLocks($employeeIds, $date, function () use ($request, $id) {
            return DB::transaction(function () use ($request, $id) {
                $booking = Booking::with('transactions')->lockForUpdate()->findOrFail($id);

                if ($booking->transactions->contains('payment_status', 1)) {
                    $this->updatePaidBookingSchedule($booking, $request);

                    // FIX: validate the new schedule before returning success.
                    $this->assertNoScheduleConflicts($booking);

                    $message = __('booking.booking_service_update', ['form' => __('booking.singular_title')]);

                    $data = Booking::with('services', 'user', 'products', 'packages', 'bookingPackages.services')->findOrFail($booking->id);

                    return response()->json(['message' => $message, 'status' => true, 'data' => new BookingResource($data)], 200);
                }

                $booking->update($request->except([
                    'services',
                    'products',
                    'packages',
                    'purchase_packages',
                    'services_id',
                    'product_variation_id',
                    'package_id',
                ]));

                $services = $request->input('services', []);
                $purchasePackages = $request->input('purchase_packages', []);

                if (is_array($services) && count($services) > 0) {
                    $this->updateBookingService($services, $booking->id);
                }

                $this->updateExistingBookingSchedule($booking, $request);

                if (is_array($purchasePackages) && count($purchasePackages) > 0) {
                    $this->updateBookingPackage($purchasePackages, $booking->id);
                }

                BookingPackages::where('booking_id', $booking->id)->update([
                    'employee_id' => $request->input('employee_id'),
                ]);

                // FIX: reject the update if it results in a double-booked employee,
                // an internal overlap, or a slot outside the employee's availability.
                $this->assertNoScheduleConflicts($booking);

                $message = __('booking.booking_service_update', ['form' => __('booking.singular_title')]);

                $data = Booking::with('services', 'user', 'products', 'packages', 'bookingPackages.services')->findOrFail($booking->id);

                return response()->json(['message' => $message, 'status' => true, 'data' => new BookingResource($data)], 200);
            }, 3); // FIX: retry up to 3x on deadlock before giving up
        });

        $updatedBooking = Booking::find($id);
        if ($updatedBooking) {
            app(\App\Services\BookingWhatsAppService::class)->enqueueCalendarUpdate($updatedBooking, $fingerprintBefore);
        }

        return $response;
    }

    private function updatePaidBookingSchedule(Booking $booking, Request $request): void
    {
        $this->updateExistingBookingSchedule($booking, $request);
    }

    private function updateExistingBookingSchedule(Booking $booking, Request $request): void
    {
        $startDateTime = $request->input('start_date_time');
        $employeeId = $request->input('employee_id');
        $branchId = $request->input('branch_id');

        $booking->update([
            'start_date_time' => $startDateTime,
            'branch_id' => $branchId ?: $booking->branch_id,
        ]);

        $nextStart = Carbon::parse($startDateTime);
        $bookingServices = BookingService::where('booking_id', $booking->id)
            ->orderBy('sequance')
            ->orderBy('id')
            ->get();

        foreach ($bookingServices as $service) {
            $service->update([
                'employee_id' => $employeeId,
                'start_date_time' => $nextStart->format('Y-m-d H:i:s'),
            ]);

            $nextStart = $nextStart->copy()->addMinutes((int) ($service->duration_min ?: 0));
        }

        BookingPackages::where('booking_id', $booking->id)->update([
            'employee_id' => $employeeId,
        ]);
    }

    /**
     * FIX: New helper — checks every service and package attached to a booking
     * three ways:
     *   1. Against each other (catches two items in the SAME booking accidentally
     *      assigned to the same employee at overlapping times).
     *   2. Against every OTHER booking for the same employee (the original conflict
     *      check).
     *   3. (optional, see $enforceEmployeeAvailability) Against the employee's actual
     *      working hours, breaks, holidays and approved leave, so a booking can't be
     *      silently saved for a time the employee doesn't actually work.
     * Throws a ValidationException on the first problem found, which aborts the
     * enclosing DB::transaction() and rolls back everything written so far.
     */
    private function assertNoScheduleConflicts(Booking $booking): void
    {
        $booking->refresh();
        $booking->load(['services', 'bookingPackages.services']);

        $this->assertEmployeesCanPerformBookedServices($booking);

        $items = [];

        foreach ($booking->services as $service) {
            if (empty($service->employee_id) || empty($service->start_date_time)) {
                continue;
            }

            $start = Carbon::parse($service->start_date_time);
            $items[] = [
                'employee_id' => (int) $service->employee_id,
                'start' => $start,
                'end' => $start->copy()->addMinutes((int) $service->duration_min),
            ];
        }

        foreach ($booking->bookingPackages as $bookingPackage) {
            if (empty($bookingPackage->employee_id) || empty($booking->start_date_time)) {
                continue;
            }

            $duration = (int) $bookingPackage->services->sum('duration_min');
            $start = Carbon::parse($booking->start_date_time);
            $items[] = [
                'employee_id' => (int) $bookingPackage->employee_id,
                'start' => $start,
                'end' => $start->copy()->addMinutes($duration),
            ];
        }

        // 1. Internal overlap check (same booking, same employee).
        $this->assertNoInternalOverlap($items);

        // 2 & 3. External conflict + availability check, per item.
        foreach ($items as $item) {
            $durationMinutes = $item['start']->diffInMinutes($item['end']);

            $this->ensureNoScheduleConflict(
                $item['employee_id'],
                $item['start']->format('Y-m-d H:i:s'),
                $durationMinutes,
                $booking->id
            );

            if ($this->shouldEnforceEmployeeAvailability()) {
                $this->assertEmployeeIsAvailable(
                    $item['employee_id'],
                    (int) $booking->branch_id,
                    $item['start'],
                    $item['end']
                );
            }
        }
    }

    /**
     * FIX: New helper — throws if any two items in the same booking share an employee
     * and overlap in time. Without this, only conflicts against OTHER bookings were
     * ever caught; a booking that double-books its own employee across two of its own
     * services would previously have been saved without complaint.
     */
    private function assertNoInternalOverlap(array $items): void
    {
        $count = count($items);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                if ($items[$i]['employee_id'] !== $items[$j]['employee_id']) {
                    continue;
                }

                if ($this->rangeOverlapsAny($items[$i]['start'], $items[$i]['end'], [$items[$j]])) {
                    throw ValidationException::withMessages([
                        'employee_id' => ['This booking assigns the same staff member to two overlapping time slots.'],
                    ]);
                }
            }
        }
    }

    /**
     * FIX: New helper — throws if the given employee already has a booking overlapping
     * the given time window on any branch, excluding $ignoreBookingId (the booking
     * currently being saved). Uses SELECT ... FOR UPDATE so two simultaneous requests
     * for the same employee/slot can't both pass the check (must be called inside a
     * DB::transaction()). Combined with the advisory lock acquired in
     * withEmployeeScheduleLocks(), this is also safe when no conflicting row exists yet.
     */
    private function ensureNoScheduleConflict(int $employeeId, string $startDateTime, int $durationMinutes, int $ignoreBookingId = 0): void
    {
        if ($employeeId <= 0) {
            return;
        }

        $duration = max($durationMinutes, 1);
        $start = Carbon::parse($startDateTime);
        $end = $start->copy()->addMinutes($duration);
        $date = $start->toDateString();

        // branchId = 0 => check across all branches, an employee can't be in two places at once.
        $busyRanges = $this->employeeBusyRanges($employeeId, 0, $date, $ignoreBookingId, true);

        if ($this->rangeOverlapsAny($start, $end, $busyRanges)) {
            throw ValidationException::withMessages([
                'employee_id' => [__('booking.slot_conflict') !== 'booking.slot_conflict'
                    ? __('booking.slot_conflict')
                    : 'This staff member already has a booking that overlaps this time slot.'],
            ]);
        }
    }

    /**
     * FIX: New helper — validates that an employee is actually scheduled to work
     * (not on leave, not a branch holiday, within their working hours, and not on a
     * break) at the given time window. Reuses the same working-config resolution used
     * to build the calendar's availability background events, so this stays in sync
     * with what the UI already shows as "available".
     */
    private function assertEmployeeIsAvailable(int $employeeId, int $branchId, Carbon $start, Carbon $end): void
    {
        $employee = User::with('branches')->find($employeeId);

        if (! $employee) {
            return; // employee existence/permissions are validated elsewhere
        }

        $effectiveBranchId = $branchId > 0 ? $branchId : $this->effectiveEmployeeBranchId($employee, 0);
        $date = $start->toDateString();
        $dayName = strtolower($start->format('l'));

        $context = $this->availabilityContextForDate(
            $date,
            $effectiveBranchId > 0 ? [$effectiveBranchId] : [],
            [$employeeId]
        );

        if ($effectiveBranchId > 0 && isset($context['holiday_branch_ids'][$effectiveBranchId])) {
            throw ValidationException::withMessages([
                'employee_id' => ['The branch is closed for a holiday on this date.'],
            ]);
        }

        if (isset($context['leave_staff_ids'][$employeeId])) {
            throw ValidationException::withMessages([
                'employee_id' => ['This staff member is on approved leave on this date.'],
            ]);
        }

        $workingConfig = $this->resolveEmployeeWorkingConfig($employee, $effectiveBranchId, $dayName, $context);

        if (! $workingConfig) {
            throw ValidationException::withMessages([
                'employee_id' => ['This staff member is not scheduled to work on this date.'],
            ]);
        }

        $workStart = Carbon::parse("{$date} {$workingConfig['start_time']}");
        $workEnd = Carbon::parse("{$date} {$workingConfig['end_time']}");

        if ($start->lt($workStart) || $end->gt($workEnd)) {
            throw ValidationException::withMessages([
                'employee_id' => ["This time is outside {$employee->full_name}'s working hours."],
            ]);
        }

        foreach ($workingConfig['breaks'] as $break) {
            if (empty($break['start_break']) || empty($break['end_break'])) {
                continue;
            }

            $breakStart = Carbon::parse("{$date} {$this->normalizeTime($break['start_break'])}");
            $breakEnd = Carbon::parse("{$date} {$this->normalizeTime($break['end_break'])}");

            if ($start->lt($breakEnd) && $end->gt($breakStart)) {
                throw ValidationException::withMessages([
                    'employee_id' => ["This time overlaps {$employee->full_name}'s break."],
                ]);
            }
        }
    }

    private function shouldEnforceEmployeeAvailability(): bool
    {
        if (! $this->enforceEmployeeAvailability) {
            return false;
        }

        return ! request()->user()?->can($this->overrideUnavailableSlotPermission);
    }

    /**
     * FIX: New helper — extracts every employee_id mentioned anywhere in the request
     * payload (top-level, per-service, and per-package) so callers can pre-acquire
     * advisory locks before writing. Best-effort: assertNoScheduleConflicts() still
     * runs afterwards as the authoritative check against whatever actually got
     * persisted, so an employee_id this heuristic misses is still caught — it just
     * won't have had the benefit of the advisory lock closing the phantom-row race.
     */
    private function extractRequestEmployeeIds(Request $request): array
    {
        $ids = [];

        if ($request->filled('employee_id')) {
            $ids[] = (int) $request->input('employee_id');
        }

        foreach ((array) $request->input('services', []) as $service) {
            if (is_array($service) && ! empty($service['employee_id'])) {
                $ids[] = (int) $service['employee_id'];
            }
        }

        foreach ((array) $request->input('purchase_packages', []) as $package) {
            if (is_array($package) && ! empty($package['employee_id'])) {
                $ids[] = (int) $package['employee_id'];
            }
        }

        return $ids;
    }

    /**
     * FIX: New helper — resolves the date to scope advisory locks to, from the
     * request's start_date_time, falling back to the given fallback (e.g. the
     * existing booking's current date on an update) or today.
     */
    private function extractRequestDate(Request $request, ?string $fallback = null): string
    {
        $raw = $request->input('start_date_time', $fallback);

        try {
            return Carbon::parse($raw ?: now())->toDateString();
        } catch (\Throwable $e) {
            return Carbon::today()->toDateString();
        }
    }

    /**
     * FIX: New helper — serializes schedule writes for a set of employees on a given
     * date using MySQL's GET_LOCK()/RELEASE_LOCK(), so two concurrent requests that
     * both try to book the same employee's free slot can't both pass the conflict
     * check (lockForUpdate() alone can't prevent this when no conflicting row exists
     * yet — there is nothing to lock). Locks are acquired in a consistent, sorted
     * order to avoid deadlocking against another request doing the same thing for an
     * overlapping set of employees. Falls back to running the callback unlocked on
     * non-MySQL connections (e.g. local testing on SQLite).
     */
    private function withEmployeeScheduleLocks(array $employeeIds, string $date, \Closure $callback)
    {
        $employeeIds = array_values(array_unique(array_filter(array_map('intval', $employeeIds), fn ($id) => $id > 0)));
        sort($employeeIds);

        if (empty($employeeIds) || DB::connection()->getDriverName() !== 'mysql') {
            return $callback();
        }

        $acquired = [];

        try {
            foreach ($employeeIds as $employeeId) {
                $lockName = $this->employeeScheduleLockName($employeeId, $date);
                $result = DB::selectOne('SELECT GET_LOCK(?, ?) AS acquired', [$lockName, 10]);

                if (! $result || (int) $result->acquired !== 1) {
                    throw ValidationException::withMessages([
                        'employee_id' => ['Another booking is being saved for this staff member right now. Please try again in a moment.'],
                    ]);
                }

                $acquired[] = $lockName;
            }

            return $callback();
        } finally {
            foreach (array_reverse($acquired) as $lockName) {
                DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [$lockName]);
            }
        }
    }

    private function employeeScheduleLockName(int $employeeId, string $date): string
    {
        return 'booking_emp_' . $employeeId . '_' . $date;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        if (config('sami.is_demo')) {
            return response()->json(['message' => __('messages.permission_denied'), 'status' => false], 200);
        }
        $booking = Booking::findOrFail($id);
        $this->checkEmployeeBookingAccess($booking);

        $booking->delete();

        $message = __('messages.delete_form', ['form' => __('booking.singular_title')]);

        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function updateStatus($id, Request $request)
    {
        $booking = Booking::with('services', 'user', 'products', 'packages', 'bookingPackages.services')->findOrFail($id);
        $this->checkEmployeeBookingAccess($booking);
        $status = $request->status;

        if (isset($request->action_type) && $request->action_type == 'update-status') {
            $status = $request->value;
        }

        $booking->update(['status' => $status]);

        $notify_type = null;

        switch ($status) {
            case 'check_in':
                $notify_type = 'check_in_booking';
                $messageTemplate = '#[[booking_id]] has been check-in successfully.';
                $notify_message = str_replace('[[booking_id]]', $id, $messageTemplate);
                break;
            case 'checkout':
                $notify_type = 'checkout_booking';
                $messageTemplate = '#[[booking_id]] has been check-out successfully.';
                $notify_message = str_replace('[[booking_id]]', $id, $messageTemplate);
                break;
            case 'completed':
                $notify_type = 'complete_booking';
                $messageTemplate = 'Booking #[[booking_id]] has been completed. Please find the attached invoice in your email.';
                $notify_message = str_replace('[[booking_id]]', $id, $messageTemplate);
                break;
            case 'cancelled':
                $notify_type = 'cancel_booking';
                $messageTemplate = 'Booking #[[booking_id]] has been cancelled.';
                $notify_message = str_replace('[[booking_id]]', $id, $messageTemplate);
                break;
        }

        if (isset($notify_type)) {
            try {
                $this->sendNotificationOnBookingUpdate($notify_type, $notify_message, $booking);
            } catch (\Exception $e) {
                \Log::error($e->getMessage());
            }
        }

        $message = __('booking.status_update');

        return response()->json(['data' => new BookingResource($booking), 'message' => $message, 'status' => true]);
    }

    public function updatePaymentStatus($id, Request $request)
    {
        if (isset($request->action_type) && $request->action_type == 'update-payment-status') {
            $status = $request->value;
        }

        $paymentStatus = (int) $request->value;
        $transactionType = $request->input('transaction_type', 'cash');

        BookingTransaction::updateOrCreate(
            ['booking_id' => $id],
            [
                'payment_status' => $paymentStatus,
                'transaction_type' => $transactionType,
            ]
        );

        if ($paymentStatus === 1) {
            Booking::where('id', $id)
                ->where('status', 'pending')
                ->update(['status' => 'confirmed']);
        }

        $message = __('booking.status_update');

        $booking = Booking::with('services', 'user', 'products', 'packages', 'bookingPackages.services')->findOrFail($id);

        return response()->json(['message' => $message, 'status' => true, 'data' => new BookingResource($booking)]);
    }

    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);

        $actionType = $request->action_type;

        $message = __('messages.bulk_update');

        switch ($actionType) {
            case 'change-status':
                $branches = Booking::whereIn('id', $ids)->get()->each->update(['status' => $request->status]);
                $message = __('messages.bulk_booking_update');
                break;

            case 'delete':
                if (config('sami.is_demo')) {
                    return response()->json(['message' => __('messages.permission_denied'), 'status' => false], 200);
                }
                Booking::whereIn('id', $ids)->get()->each->delete();
                $message = __('messages.bulk_booking_delete');
                break;

            default:
                return response()->json(['status' => false, 'message' => __('booking.booking_action_invalid')]);
                break;
        }

        return response()->json(['status' => true, 'message' => $message]);
    }

    public function booking_slots(Request $request)
    {
        try {
            $date = Carbon::parse($request->date)->toDateString();
        } catch (\Throwable $e) {
            return response()->json(['status' => true, 'data' => []]);
        }

        if (Carbon::parse($date)->lt(Carbon::today())) {
            return response()->json(['status' => true, 'data' => []]);
        }

        $branchId = (int) $request->branch_id;
        $employeeId = (int) $request->employee_id;
        $serviceDuration = max(0, (int) $request->service_duration);
        $ignoreBookingId = (int) $request->booking_id;

        if ($employeeId <= 0) {
            if ($branchId <= 0) {
                return response()->json(['status' => true, 'data' => []]);
            }

            $employees = User::with('branches')
                ->active()
                ->varified()
                ->employee()
                ->where('is_manager', 0)
                ->whereHas('branches', function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                })
                ->get();

            $slots = $employees
                ->flatMap(fn ($employee) => $this->buildBookingSlotsForEmployee($employee, $date, $branchId, $serviceDuration, $ignoreBookingId))
                ->unique('value')
                ->sortBy('value')
                ->values()
                ->all();

            return response()->json(['status' => true, 'data' => $slots]);
        }

        $employee = User::with('branches')
            ->active()
            ->varified()
            ->employee()
            ->where('is_manager', 0)
            ->find($employeeId);

        if (!$employee) {
            return response()->json(['status' => true, 'data' => []]);
        }

        if ($branchId > 0 && !$employee->branches->contains('branch_id', $branchId)) {
            return response()->json(['status' => true, 'data' => []]);
        }

        $effectiveBranchId = $this->effectiveEmployeeBranchId($employee, $branchId);
        if ($effectiveBranchId <= 0) {
            return response()->json(['status' => true, 'data' => []]);
        }

        $slots = $this->buildBookingSlotsForEmployee($employee, $date, $effectiveBranchId, $serviceDuration, $ignoreBookingId);

        return response()->json(['status' => true, 'data' => $slots]);
    }

    private function buildBookingSlotsForEmployee(User $employee, string $date, int $branchId, int $serviceDuration, int $ignoreBookingId = 0): array
    {
        if (Carbon::parse($date)->lt(Carbon::today())) {
            return [];
        }

        $dayName = strtolower(Carbon::parse($date)->format('l'));
        $context = [
            'day_name' => $dayName,
            'holiday_branch_ids' => Holiday::where('branch_id', $branchId)
                ->whereDate('date', $date)
                ->pluck('branch_id')
                ->mapWithKeys(fn ($id) => [(int) $id => true])
                ->all(),
            'leave_staff_ids' => StaffLeavePeriod::where('staff_id', $employee->id)
                ->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->pluck('staff_id')
                ->mapWithKeys(fn ($id) => [(int) $id => true])
                ->all(),
            'staff_working_hours' => StaffWorkingHour::where('staff_id', $employee->id)
                ->where('day_of_week', $dayName)
                ->orderBy('id', 'desc')
                ->get()
                ->unique('staff_id')
                ->keyBy('staff_id'),
            'business_hours' => BussinessHour::where('branch_id', $branchId)
                ->where('day', $dayName)
                ->where('is_holiday', 0)
                ->orderBy('id', 'desc')
                ->get(),
        ];

        if (isset($context['holiday_branch_ids'][$branchId]) || isset($context['leave_staff_ids'][(int) $employee->id])) {
            return [];
        }

        $workingConfig = $this->resolveEmployeeWorkingConfig($employee, $branchId, $dayName, $context);
        if (!$workingConfig) {
            return [];
        }

        $slotInterval = $this->slotIntervalMinutes();
        $duration = $serviceDuration > 0 ? $serviceDuration : $slotInterval;
        $start = Carbon::parse("{$date} {$workingConfig['start_time']}");
        $end = Carbon::parse("{$date} {$workingConfig['end_time']}");

        if ($end->lessThanOrEqualTo($start)) {
            return [];
        }

        $breakRanges = $this->slotBreakRanges($date, $workingConfig['breaks']);
        // NOTE: booking_slots() checks a single branch's busy ranges for display purposes
        // only (it's just populating the dropdown). The authoritative, cross-branch check
        // that actually blocks a conflicting save happens in ensureNoScheduleConflict().
        $busyRanges = $this->employeeBusyRanges($employee->id, $branchId, $date, $ignoreBookingId);
        $slots = [];

        for ($cursor = $start->copy(); $cursor->copy()->addMinutes($duration)->lessThanOrEqualTo($end); $cursor->addMinutes($slotInterval)) {
            $candidateEnd = $cursor->copy()->addMinutes($duration);

            if ($this->rangeOverlapsAny($cursor, $candidateEnd, $breakRanges) || $this->rangeOverlapsAny($cursor, $candidateEnd, $busyRanges)) {
                continue;
            }

            $slots[] = [
                'value' => $cursor->format('Y-m-d H:i:s'),
                'label' => $cursor->format('h:i A'),
                'disabled' => false,
            ];
        }

        return $slots;
    }

    private function slotIntervalMinutes(): int
    {
        $slotDuration = (string) setting('slot_duration');
        $parts = explode(':', $slotDuration);
        $hours = (int) ($parts[0] ?? 0);
        $minutes = (int) ($parts[1] ?? 15);
        $totalMinutes = ($hours * 60) + $minutes;

        return $totalMinutes > 0 ? $totalMinutes : 15;
    }

    private function slotBreakRanges(string $date, array $breaks): array
    {
        return array_values(array_filter(array_map(function ($break) use ($date) {
            if (empty($break['start_break']) || empty($break['end_break'])) {
                return null;
            }

            $start = Carbon::parse("{$date} {$this->normalizeTime($break['start_break'])}");
            $end = Carbon::parse("{$date} {$this->normalizeTime($break['end_break'])}");

            if ($end->lessThanOrEqualTo($start)) {
                return null;
            }

            return ['start' => $start, 'end' => $end];
        }, $breaks)));
    }

    /**
     * FIX: $branchId is now treated as optional — pass 0 to check an employee's busy
     * ranges across *all* branches (used by ensureNoScheduleConflict(), since an
     * employee can't legitimately be double-booked just because the two bookings are in
     * different branches). $lockForUpdate row-locks the matching rows so this can be
     * safely used inside a DB::transaction() to prevent race conditions between two
     * concurrent writes.
     */
    private function employeeBusyRanges(int $employeeId, int $branchId, string $date, int $ignoreBookingId = 0, bool $lockForUpdate = false): array
    {
        $serviceRanges = BookingService::with('booking')
            ->where('employee_id', $employeeId)
            ->when($ignoreBookingId > 0, fn ($q) => $q->where('booking_id', '!=', $ignoreBookingId))
            ->when($lockForUpdate, fn ($q) => $q->lockForUpdate())
            ->whereHas('booking', function ($q) use ($branchId, $date) {
                $q->when($branchId > 0, fn ($qq) => $qq->where('branch_id', $branchId))
                    ->whereDate('start_date_time', $date)
                    ->where('status', '!=', 'cancelled');
            })
            ->orderBy('id')
            ->get()
            ->map(function ($service) {
                $start = Carbon::parse($service->start_date_time);
                return [
                    'start' => $start,
                    'end' => $start->copy()->addMinutes((int) $service->duration_min),
                ];
            })
            ->all();

        $packageRanges = BookingPackages::with('booking', 'services')
            ->where('employee_id', $employeeId)
            ->when($ignoreBookingId > 0, fn ($q) => $q->where('booking_id', '!=', $ignoreBookingId))
            ->when($lockForUpdate, fn ($q) => $q->lockForUpdate())
            ->whereHas('booking', function ($q) use ($branchId, $date) {
                $q->when($branchId > 0, fn ($qq) => $qq->where('branch_id', $branchId))
                    ->whereDate('start_date_time', $date)
                    ->where('status', '!=', 'cancelled');
            })
            ->orderBy('id')
            ->get()
            ->map(function ($package) {
                $duration = (int) $package->services->sum('duration_min');
                $start = Carbon::parse($package->booking->start_date_time);
                return [
                    'start' => $start,
                    'end' => $start->copy()->addMinutes($duration),
                ];
            })
            ->all();

        return array_merge($serviceRanges, $packageRanges);
    }

    private function rangeOverlapsAny(Carbon $start, Carbon $end, array $ranges): bool
    {
        foreach ($ranges as $range) {
            if ($start->lt($range['end']) && $end->gt($range['start'])) {
                return true;
            }
        }

        return false;
    }

    public function payment_create(Request $request)
    {

        $booking_id = $request->booking_id;
        $booking = Booking::find($booking_id);
        $booking_services = BookingService::where('booking_id', $booking_id)->get();

        if ($request->has('userPackageserviceIds') && !empty($request->userPackageserviceIds)) {
            $userPackageserviceIds = $request->userPackageserviceIds;
            if (is_string($userPackageserviceIds)) {
                $userPackageserviceIds = explode(',', $userPackageserviceIds);
                $userPackageserviceIds = array_map('intval', $userPackageserviceIds); // Convert each ID to integer
            }
            $userPackageServices = UserPackageServices::whereIn('package_service_id', $userPackageserviceIds)
                ->with('packageService')
                ->get();
            if ($userPackageServices) {
                $coveredServiceIds = $userPackageServices->pluck('packageService.service_id')->toArray();

                $total_service_amount = $booking_services->reduce(function ($carry, $bookingService) use ($coveredServiceIds) {
                    if (!in_array($bookingService->service_id, $coveredServiceIds)) {
                        $carry += $bookingService->service_price;
                    } else {
                        $carry += 0;
                    }
                    return $carry;
                }, 0);
            }
        } else {
            $total_service_amount = $booking_services->sum('service_price');
        }

        $booking_products = BookingProduct::where('booking_id', $booking_id)->with('product')->get();

        $discounted_product_amount = getproductDiscountAmount($booking_products);
        $total_product_amount = BookingProduct::where('booking_id', $booking_id)->sum(\DB::raw('product_qty * product_price'));
        $userPackageRedeem = UserPackageRedeem::where('booking_id', $booking_id)->get();
        $discountedservice_amount = $userPackageRedeem->sum('service_price');
        // $package_amount = UserPackage::where('booking_id', $booking_id)->with('package')->get();
        // $total_package_amount = $package_amount->sum('package_price');
        $package_amount = BookingPackages::where('booking_id', $booking_id)->with('package')->get();
        $total_package_amount = $package_amount->sum('package_price');
        $product_amount = $total_product_amount - $discounted_product_amount;
        if ($discountedservice_amount) {
            $total_service_amount = $total_service_amount - $discountedservice_amount;
        }
        $currency = \Currency::getDefaultCurrency();
        $payment_methods = $booking->branch->payment_method;
        $constant = Constant::where('type', 'PAYMENT_METHODS')->whereIn('name', $payment_methods)->get();
        $payment_methods = $constant->map(function ($row) {
            return [
                'id' => $row->name,
                'text' => $row->value,
            ];
        })->toArray();
        $taxes = Tax::active();
        $coupon = UserCouponRedeem::where('booking_id', $booking_id)->first();
        $data = [
            'booking_amounts' => [
                'amount' => $total_service_amount,
                'product_amount' => $product_amount,
                'package_amount' => $total_package_amount,
                'currency' => $currency->currency_symbol,
            ],
            'PAYMENT_METHODS' => $payment_methods,
            'tax' => $taxes
                ->whereNull('module_type')
                ->orWhere('module_type', 'services')->where('status', 1)->get(),
            'userpackageRedeem' => $userPackageRedeem,
            'coupon' => $coupon,
        ];

        return response()->json(['status' => true, 'data' => $data]);
    }

    public function booking_payment(Request $request, Booking $booking_id)
    {
        $data = $request->all();

        $booking_id = $booking_id['id'];
        if ($request->has('packageService') && !empty($request->packageService)) {
            foreach ($request->packageService as $service) {
                $serviceId = $service['service_id'];
                $discountPrice = $service['discount_price'];
                BookingService::where('booking_id', $booking_id)
                    ->where('service_id', $serviceId)
                    ->update(['service_price' => 0]);
            }
        }


        $responseData = $this->getpayment_method($data, $booking_id);
        $this->updateUserPackageRedeem($request->packageService, $booking_id);
        $booking_product = BookingProduct::where('booking_id', $booking_id)->get();

        $booking_details = Booking::where('id', $booking_id)->with('payment')->first();
        if ($booking_product->isNotEmpty()) {
            $orderId = $this->createCart($booking_product, $booking_details);

            BookingProduct::where('booking_id', $booking_id)->update(['order_id' => $orderId]);
        }

        return response()->json(['status' => true, 'data' => $responseData]);
    }

    public function booking_payment_update(Request $request, $booking_transaction_id)
    {
        $data = $request->all();

        $responseData = $this->getrazorpaypayments($data, $booking_transaction_id);

        if (isset($responseData['booking'])) {
            $queryData = Booking::find($responseData['booking']->id);

            $messageTemplate = 'Booking #[[booking_id]] has been completed. Please find the attached invoice in your email.';
            $notify_message = str_replace('[[booking_id]]',$responseData['booking']->id, $messageTemplate);
            try {
                $this->sendNotificationOnBookingUpdate('complete_booking', $notify_message,$queryData);
            } catch (\Exception $e) {
                \Log::error($e->getMessage());
            }
        }


        return response()->json(['status' => true, 'data' => $responseData]);
    }

    /**
     * FIX: Wrapped in DB::transaction() and now validates the resulting schedule with
     * assertNoScheduleConflicts() before returning success.
     */
    public function checkout(Booking $booking_id, Request $request)
    {
        $booking_id->loadMissing('services', 'bookingPackages');
        $employeeIds = array_merge(
            $this->extractRequestEmployeeIds($request),
            $booking_id->services->pluck('employee_id')->all(),
            $booking_id->bookingPackages->pluck('employee_id')->all()
        );
        $date = Carbon::parse($booking_id->start_date_time)->toDateString();

        return $this->withEmployeeScheduleLocks($employeeIds, $date, function () use ($booking_id, $request) {
            return DB::transaction(function () use ($booking_id, $request) {
                // $this->updateBookingPackage($request->purchase_package, $booking_id->id);

                $this->updateBookingService($request->services, $booking_id->id);

                $this->updateBookingProduct($request->products, $booking_id->id);

                // FIX: reject the checkout if it results in a double-booked employee.
                $this->assertNoScheduleConflicts($booking_id);

                $queryData = Booking::with('services', 'user', 'products', 'packages', 'bookingPackages.services')->findOrFail($booking_id->id);

                return response()->json(['status' => true, 'data' => new BookingResource($queryData), 'message' => __('booking.booking_service_update')]);
            }, 3); // FIX: retry up to 3x on deadlock before giving up
        });
    }

    public function stripe_payment(Request $request)
    {
        $data = $request->data;

        $checkout_session = $this->getstripepayments($data);

        if (isset($checkout_session['message'])) {
            return response()->json(['status' => false, 'data' => $checkout_session]);
        } else {
            BookingTransaction::where('id', $data['booking_transaction_id'])->update(['request_token' => $checkout_session['id']]);

            return response()->json(['status' => true, 'data_url' => $checkout_session->url, 'data' => $checkout_session]);
        }
    }

    public function payment_success($id)
    {
        $booking_transaction = BookingTransaction::where('id', $id)->first();

        $request_token = $booking_transaction['request_token'];

        $booking_id = $booking_transaction['booking_id'];

        $session_object = $this->getstripePaymnetId($request_token);

        if ($session_object['payment_intent'] !== '' && $session_object['payment_status'] == 'paid') {
            BookingTransaction::where('id', $id)->get()->each->update(['external_transaction_id' => $session_object['payment_intent'], 'payment_status' => 1]);

            Booking::where('id', $booking_id)->get()->each->update(['status' => 'completed']);

            $queryData = Booking::where('id', $booking_id)->first();
            try {
                $messageTemplate = 'Booking #[[booking_id]] has been completed. Please find the attached invoice in your email.';
                $notify_message = str_replace('[[booking_id]]',  $queryData->id, $messageTemplate);
                $this->sendNotificationOnBookingUpdate('complete_booking',$notify_message, $queryData);
            } catch (\Exception $e) {
                \Log::error($e->getMessage());
            }
        }

        return redirect()->route('backend.bookings.index');
    }

    /**
     * Show the specified resource.
     *
     * @param  int  $id
     * @return Renderable
     */
    public function viewInvoice(Request $request)
    {
        $order = Booking::find($request->id);


        $booking = Booking::with(['services', 'user', 'products', 'userCouponRedeem', 'packages'])->where('status', 'completed')->find($request->id);

        if ($booking == null) {
            return abort(500);
        }

        if (is_null($booking)) {
            return response()->json(['message' => __('messages.booking_not_found')], 404);
        }

        $data = $this->bookingDetail($booking);

        $data = (object) [
            'booking' => new BookingResource($booking),
            'services_total_amount' => $data['serviceAmount'],
            'booking_transaction' => $data['bookingTransaction'],
            'product_amount' => $data['sumDiscountedPrice'],
            'tax_amount' => $data['tax_amount'],
            'coupon_discount' => $data['coupon_discount'],
            'grand_total' => $data['grand_total'],
            'package_amount' => $data['packageAmount'],
        ];

        return view('booking::backend.invoice', compact('data'));
    }
 
    public function downloadInvoice(Request $request)
    {
        $booking = Booking::with(['services', 'user', 'products'])->where('status', 'completed')->find($request->id);

        $booking['detail'] = $this->bookingDetail($booking);
        $filename = 'Invoice_' . $request->id . '.pdf';
        // Prepare data for notification
        $data = $this->sendNotificationOnBookingUpdate('complete_booking', 'Notification message', $booking, false);
        if ($data === false) {
            return response()->json(['status' => false, 'message' => 'Failed to prepare booking data for notification'], 500);
        }

        // Render the view for the PDF
        $view = view("mail.invoice-templates." . setting('template'), ['data' => $data['booking']])->render();
        $pdf = Pdf::loadHTML($view);

        if ($request->is('api/*')) {
            // Handle API request
            $baseDirectory = storage_path('app/public');
            $highestDirectory = collect(File::directories($baseDirectory))->map(function ($directory) {
                return basename($directory);
            })->max() ?? 0;
            $nextDirectory = intval($highestDirectory) + 1;
            while (File::exists($baseDirectory . '/' . $nextDirectory)) {
                $nextDirectory++;
            }
            $newDirectory = $baseDirectory . '/' . $nextDirectory;
            File::makeDirectory($newDirectory, 0777, true);

            $filename = 'invoice_' . $request->id . '.pdf';
            $filePath = $newDirectory . '/' . $filename;

            $pdf->save($filePath);

            $url = url('storage/' . $nextDirectory . '/' . $filename);
            if (!empty($url)) {
                return response()->json(['status' => true, 'link' => $url], 200);
            } else {
                return response()->json(['status' => false, 'message' => __('Url Not Found')], 404);
            }
        } else {
            // Handle non-API request
            // return $pdf->download($filename);
            return response()->streamDownload(
                function () use ($pdf) {
                    echo $pdf->output();
                },
                "invoice.pdf",
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="invoice.pdf"',
                ]
            );
        }
    }

    /**
     * Enforce the employee/service relation at the write boundary. The calendar
     * selector is only a convenience and must not be the source of authorization.
     */
    private function assertEmployeesCanPerformBookedServices(Booking $booking): void
    {
        foreach ($booking->services as $bookingService) {
            $employeeId = (int) $bookingService->employee_id;
            $serviceId = (int) $bookingService->service_id;

            if ($employeeId <= 0 || $serviceId <= 0) {
                throw ValidationException::withMessages([
                    'services' => ['كل خدمة في الحجز يجب أن يكون لها موظف صالح.'],
                ]);
            }

            $canPerformService = ServiceEmployee::query()
                ->where('employee_id', $employeeId)
                ->where('service_id', $serviceId)
                ->exists();

            if (! $canPerformService) {
                $employeeName = User::query()->whereKey($employeeId)->value('first_name') ?: "#{$employeeId}";
                $serviceName = Service::query()->whereKey($serviceId)->value('name');
                $serviceLabel = $this->localizedServiceName($serviceName) ?: "#{$serviceId}";

                throw ValidationException::withMessages([
                    'employee_id' => ["الموظف {$employeeName} غير مخصص لتقديم خدمة {$serviceLabel}."],
                ]);
            }
        }
    }

    private function localizedServiceName($name): string
    {
        if (is_array($name)) {
            return (string) ($name[app()->getLocale()] ?? $name['ar'] ?? $name['en'] ?? '');
        }

        if (! is_string($name)) {
            return '';
        }

        $decoded = json_decode($name, true);

        if (is_array($decoded)) {
            return (string) ($decoded[app()->getLocale()] ?? $decoded['ar'] ?? $decoded['en'] ?? '');
        }

        return $name;
    }

}
