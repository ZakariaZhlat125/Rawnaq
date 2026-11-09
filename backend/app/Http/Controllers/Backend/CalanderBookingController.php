<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingService;
use Modules\Service\Models\Service;

class CalanderBookingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('permission:view_booking')->only(['getservices', 'emplouee', 'getAllByTime', 'getAllByDay']);
        $this->middleware('permission:add_booking')->only('store');
        $this->middleware('permission:edit_booking')->only('update');
        $this->middleware('permission:delete_booking')->only('destroy');
    }

    public function getservices()
    {
        $services = Service::where('status', 1)->get();

        return response()->json($services);
    }

    public function emplouee()
    {
        $viewer = Auth::user();
        if ($viewer && $viewer->hasRole('employee') && ! $viewer->hasRole('admin') && ! $viewer->hasRole('manager')) {
            $employees = User::role('employee')->where('id', $viewer->id)->get();
        } else {
            $employees = User::role('employee')->where('status', 1)->get();
        }

        return response()->json([
            'employees' => $employees,
        ]);
    }

    public function store(Request $request)
    {
        $viewer = Auth::user();
        if (! $viewer) {
            return response()->json(['message' => 'غير مصرح.'], 401);
        }

        try {
            $data = $request->validate([
                'n_name' => 'required|string|max:255',
                'mobile_no' => 'required|string|max:20',
                'neighborhood' => 'nullable|string|max:255',
                'gender' => 'nullable|in:men,women',
                'service_group_id' => 'nullable|integer',
                'service_id' => 'required|integer|exists:services,id',
                'date' => 'required|date',
                'time' => 'required|string',
                'branch' => 'nullable|integer|exists:branches,id',
                'staff_id' => 'required|integer|exists:users,id',
                'agreed' => 'nullable|boolean',
                'auto_change_staff' => 'nullable|boolean',
                'note' => 'nullable|string',
            ]);

            // Scope employee creation: employee cannot create bookings for other staff
            if ($viewer->hasRole('employee') && ! $viewer->hasRole('admin') && ! $viewer->hasRole('manager')) {
                if ((int) $data['staff_id'] !== (int) $viewer->id) {
                    return response()->json(['message' => 'غير مصرح لك بإنشاء حجز لموظف آخر.'], 403);
                }
            }

            $startDateTime = Carbon::parse($data['date'] . ' ' . $data['time'])->format('Y-m-d H:i:s');

            // Find or create customer
            $customer = User::firstOrCreate(
                ['mobile' => $data['mobile_no']],
                [
                    'first_name' => $data['n_name'],
                    'last_name' => '',
                    'gender' => $data['gender'] ?? 'female',
                    'status' => 1,
                ]
            );

            $booking = DB::transaction(function () use ($data, $startDateTime, $customer, $viewer) {
                $booking = new Booking();
                $booking->start_date_time = $startDateTime;
                $booking->user_id = $customer->id;
                $booking->branch_id = $data['branch'] ?? 1;
                $booking->created_by = $viewer->id;
                $booking->status = 'pending';
                $booking->note = $data['note'] ?? null;
                $booking->save();

                $service = Service::find($data['service_id']);

                BookingService::create([
                    'booking_id' => $booking->id,
                    'service_id' => $data['service_id'],
                    'employee_id' => $data['staff_id'],
                    'start_date_time' => $startDateTime,
                    'service_price' => $service?->default_price ?? 0,
                    'duration_min' => $service?->duration_min ?? 30,
                    'sequance' => 1,
                    'created_by' => $viewer->id,
                ]);

                return $booking;
            });

            return response()->json([
                'message' => 'تم إنشاء الحجز بنجاح.',
                'booking' => $booking->load('bookingService', 'user'),
            ], 201);
        } catch (\Exception $e) {
            Log::error('CalanderBooking Store Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'حدث خطأ أثناء حفظ الحجز.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $viewer = Auth::user();
        $booking = Booking::with('bookingService')->findOrFail($id);

        if ($viewer->hasRole('employee') && ! $viewer->hasRole('admin') && ! $viewer->hasRole('manager')) {
            $isAssigned = $booking->bookingService()->where('employee_id', $viewer->id)->exists();
            if (! $isAssigned) {
                return response()->json(['message' => 'غير مصرح لك بتعديل هذا الحجز.'], 403);
            }
        }

        $validated = $request->validate([
            'date' => 'nullable|date',
            'time' => 'nullable|string',
            'status' => 'nullable|string|in:pending,confirmed,cancelled,completed,check_in',
            'note' => 'nullable|string',
        ]);

        if (! empty($validated['date']) && ! empty($validated['time'])) {
            $booking->start_date_time = Carbon::parse($validated['date'] . ' ' . $validated['time'])->format('Y-m-d H:i:s');
            foreach ($booking->bookingService as $bs) {
                $bs->start_date_time = $booking->start_date_time;
                $bs->save();
            }
        }

        if (isset($validated['status'])) {
            $booking->status = $validated['status'];
        }
        if (isset($validated['note'])) {
            $booking->note = $validated['note'];
        }

        $booking->save();

        return response()->json([
            'message' => 'تم تعديل الحجز بنجاح.',
            'booking' => $booking->fresh(['bookingService']),
        ]);
    }

    public function destroy($id)
    {
        $viewer = Auth::user();
        $booking = Booking::with('bookingService')->findOrFail($id);

        if ($viewer->hasRole('employee') && ! $viewer->hasRole('admin') && ! $viewer->hasRole('manager')) {
            $isAssigned = $booking->bookingService()->where('employee_id', $viewer->id)->exists();
            if (! $isAssigned) {
                return response()->json(['message' => 'غير مصرح لك بحذف هذا الحجز.'], 403);
            }
        }

        $booking->delete();

        return response()->json(['message' => 'تم حذف الحجز بنجاح.']);
    }

    public function getAllByTime(Request $request)
    {
        $viewer = Auth::user();
        $date = $request->get('date', now()->toDateString());

        $query = Booking::with(['bookingService.service', 'bookingService.employee', 'user'])
            ->whereDate('start_date_time', $date)
            ->where('status', '!=', 'cancelled');

        if ($viewer->hasRole('employee') && ! $viewer->hasRole('admin') && ! $viewer->hasRole('manager')) {
            $query->whereHas('bookingService', fn ($s) => $s->where('employee_id', $viewer->id));
        }

        return response()->json($query->orderBy('start_date_time')->get());
    }

    public function getAllByDay(Request $request)
    {
        $viewer = Auth::user();
        $startDate = $request->get('start_date', now()->startOfWeek()->toDateString());
        $endDate = $request->get('end_date', now()->endOfWeek()->toDateString());

        $query = Booking::with(['bookingService.service', 'bookingService.employee', 'user'])
            ->whereBetween(DB::raw('DATE(start_date_time)'), [$startDate, $endDate])
            ->where('status', '!=', 'cancelled');

        if ($viewer->hasRole('employee') && ! $viewer->hasRole('admin') && ! $viewer->hasRole('manager')) {
            $query->whereHas('bookingService', fn ($s) => $s->where('employee_id', $viewer->id));
        }

        return response()->json($query->orderBy('start_date_time')->get());
    }
}
