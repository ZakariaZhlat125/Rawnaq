<?php

namespace App\Http\Controllers;


use App\Models\User;
use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Package\Models\UserPackage;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingService;
use Modules\BussinessHour\Models\BussinessHour;
use Modules\Package\Models\BookingPackages;
use Modules\Package\Models\Package;


class HomeBookingController extends Controller
{
    
    public function branchs($id)
    {
        $branches = Branch::where('status' , 1)->whereNull('deleted_by')->whereHas('address', function ($query) use ($id) {
            $query->where('state', $id);
        })
        ->with('address.state_data')
        ->get();
    
        return response()->json($branches);
    }
    
    public function allbranchs()
    {
        $branches = Branch::where('status' ,  1)->whereNull('deleted_by')->get();
    
        return response()->json($branches);
    }
    
    public function getServiceGroups(Request $request)
    {
        $is_home = (int) $request->get('is_home');
    
        $query = DB::table('categories')
            ->whereNull('parent_id')
            ->whereNull('deleted_at')
            ->where('status', 1);
    
        if ($is_home) {
            $query->where('is_visible', 1);
        }
    
        $groups = $query->orderBy('sort_order')->orderBy('id')->get()->map(function ($item) {
    
            $media = DB::table('media')
                ->where('model_type', 'Modules\\Category\\Models\\Category')
                ->where('model_id', $item->id)
                ->where('collection_name', 'feature_image')
                ->first();
    
            $item->image = $media
                ? asset('storage/uploads/' . $media->id . '/' . $media->file_name)
                : asset('images/default.jpg');
    
            return $item;
        });
    
        return response()->json($groups);
}
    
    public function getServicesByGroup($serviceGroupId, $branchId)
    {
        if($branchId != 0){
        $branch = Branch::find($branchId);
    
        $services = $branch->services()
            ->where('category_id', $serviceGroupId)
            ->where('status', 1)
            ->where('is_visible', 0)
            ->get();
        }else{
        $services = DB::table('services')
            ->where('category_id', $serviceGroupId)
            ->where('status', 1)
            ->where('is_visible', 1)
            ->whereNull('deleted_by')
            ->whereNull('deleted_at')
            ->get();
        }
        return response()->json($services);
}
    
    public function index(Request $request) 
    {
        $branchId = (int) $request->get('branch_id');
        $serviceId = (int) $request->get('service_id');
    
        $query = DB::table('users')
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->join('branch_employee', 'users.id', '=', 'branch_employee.employee_id')
            ->join('service_employees', 'users.id', '=', 'service_employees.employee_id')
            ->where('roles.name', 'employee')
            ->where('model_has_roles.model_type', \App\Models\User::class)
            ->where('users.is_manager', 0)
            ->where('users.status', 1)
            ->where('users.is_banned', 0)
            ->whereNull('users.deleted_at');
    
        if ($branchId != 0) {
            $query->where('branch_employee.branch_id', $branchId);
        }
    
        if ($branchId == 0) {
            $query->where('show_in_home_booking', 1);
        }
    
        if ($serviceId != 0) {
            $query->where('service_employees.service_id', $serviceId);
        }
    
        $employees = $query->select('users.*')->distinct()->get();
    
        // Add rating data to each employee
        $availabilityDate = $request->get('date') ?: Carbon::now('Asia/Riyadh')->toDateString();

        $employees = $employees->map(function ($employee) use ($availabilityDate) {
            $user = User::find($employee->id);
            $ratings = $user->rating ?? collect([]);
            $employee->avg_rating = $ratings->count() > 0 ? (float) number_format($ratings->avg('rating'), 2) : 0;
            $employee->total_reviews = $ratings->count();

            $employee->availability_start = null;
            $employee->availability_end = null;

            if ($user) {
                try {
                    $branchId = optional($user->branch)->branch_id;
                    $baseShiftId = $user->shift?->shift_id;
                    $shiftId = $this->resolveWeeklyRotatingShiftId($baseShiftId, $availabilityDate, $user->id);
                    $dayName = strtolower(Carbon::createFromFormat('Y-m-d', $availabilityDate, 'Asia/Riyadh')->format('l'));

                    $workingHours = BussinessHour::where('branch_id', $branchId)
                        ->where('day', $dayName)
                        ->where('is_holiday', 0)
                        ->where('shift_id', $shiftId ?: -1)
                        ->orderBy('id', 'desc')
                        ->first();

                    if ($workingHours) {
                        $employee->availability_start = Carbon::parse($workingHours->start_time)->format('H:i');
                        $employee->availability_end = Carbon::parse($workingHours->end_time)->format('H:i');
                    }
                } catch (\Throwable $e) {
                    $employee->availability_start = null;
                    $employee->availability_end = null;
                }
            }

            return $employee;
        });
    
        return response()->json($employees);
}

    public function getAvailableTimes(Request $request ,$date, $staffId)
    {
        $timezone = 'Asia/Riyadh'; 
        if (!is_string($date) || !Carbon::canBeCreatedFromFormat($date, 'Y-m-d')) {
            return response()->json([]);
        }
        
        $user = User::find($staffId);
        if (!$user || !(int) $user->status || (int) $user->is_banned) {
            return response()->json([]);
        }
        
        $branchId = optional($user->branch)->branch_id;
        if (!$branchId) {
            return response()->json([]);
        }
        $baseShiftId = $user->shift?->shift_id;
        $shift = $this->resolveWeeklyRotatingShiftId($baseShiftId, $date, $user->id);


        try {
            $dayName = strtolower(Carbon::createFromFormat('Y-m-d', $date, $timezone)->format('l'));
        } catch (\Throwable $e) {
            return response()->json([]);
        }
        
        $serve_book_min = max(1, (int) $request->query('Increasing', 30));

        if (!$shift) return response()->json([]);
        $workingHours = BussinessHour::where('branch_id', $branchId)->where('day', $dayName)->where('is_holiday', 0)->where('shift_id', $shift)->orderBy('id', 'desc')->first();

        if (!$workingHours) {
            return response()->json([]);
        }

        // Both HH:mm and HH:mm:ss are supported by the dated parsing below.
        foreach ([$workingHours->start_time, $workingHours->end_time] as $time) {
            if (!is_string($time) || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $time)) {
                return response()->json([]);
            }
        }
        $bookedTimes = BookingService::where('employee_id', $staffId)->whereDate('start_date_time', $date)
            ->whereHas('booking', function ($q) {
                $q->whereIn('status', ['pending', 'confirmed', 'check_in']);
            })
            ->get(['start_date_time', 'duration_min'])
            ->flatMap(function ($booking) {
                $times = [];
                $start = Carbon::parse($booking->start_date_time);
                $duration = max(1, (int) ($booking->duration_min ?? 0));
                $steps = floor($duration / 1);
                for ($i = 0; $i < $steps; $i++) {
                    $times[] = $start->copy()->addMinutes($i * 1)->format('H:i');
                }

                return $times;
            })->unique()->values()->toArray();


        $bookedLookup = array_fill_keys($bookedTimes, true);
        $breaks = $this->normalizeBreaks($workingHours->breaks, $date);
        
        $availableTimes = [];

        $workStart = Carbon::parse($date . ' ' . $workingHours->start_time, $timezone)->startOfMinute();
        $workEnd   = Carbon::parse($date . ' ' . $workingHours->end_time, $timezone)->startOfMinute();
        
        if ($workEnd->lte($workStart)) {
            $workEnd->addDay();
        }

        $current = $workStart->copy();

        $isToday = Carbon::createFromFormat('Y-m-d', $date, $timezone)->isToday();
        $now = Carbon::now($timezone)->startOfMinute();

        while ($current < $workEnd) {
            $timeStr = $current->format('H:i');
        
            if ($isToday && $current->lt($now)) {
                $current->addMinute();
                continue;
            }
        
            $isInBreak = false;
            foreach ($breaks as $break) {
                if ($current->gte($break['start']) && $current->lt($break['end'])) {
                    $isInBreak = true;
                    break;
                }
            }
        
            if (!$isInBreak && !isset($bookedLookup[$timeStr])) {
                $availableTimes[] = $timeStr;
            }
        
            $current->addMinute();
        }
        $availableTimes2 = $this->filterAvailableTimes($availableTimes, $serve_book_min);
        $availableTimes3 = $availableTimes2; // Continuous free runs already exclude every booked minute.
        $availableTimes4 = $this->pickSlotStartsByDuration($availableTimes3, $serve_book_min, $workStart, $workEnd);

        return response()->json($availableTimes4);
    }


    private function pickSlotStartsByDuration(array $availableTimes, int $serviceDuration, Carbon $workStart, Carbon $workEnd): array
    {
        $serviceDuration = max(1, (int) $serviceDuration);
        if (empty($availableTimes)) {
            return [];
        }

        $lookup = array_fill_keys($availableTimes, true);
        $slots = [];
        $cursor = $workStart->copy();

        while ($cursor->copy()->addMinutes($serviceDuration)->lte($workEnd)) {
            $time = $cursor->format('H:i');
            if (isset($lookup[$time])) {
                $slots[] = $time;
            }
            $cursor->addMinutes($serviceDuration);
        }

        return $slots;
    }

    private function normalizeBreaks($breaks, string $date): array
    {
        if (is_string($breaks)) {
            $breaks = json_decode($breaks, true);
        }

        if (!is_array($breaks)) {
            return [];
        }

        $normalized = [];

        foreach ($breaks as $break) {
            if (is_object($break)) {
                $break = (array) $break;
            }

            if (!is_array($break)) {
                continue;
            }

            $startBreak = $break['start_break'] ?? $break['start'] ?? null;
            $endBreak = $break['end_break'] ?? $break['end'] ?? null;

            if (!$startBreak || !$endBreak) {
                continue;
            }

            try {
                $start = Carbon::parse($date . ' ' . $startBreak, 'Asia/Riyadh')->startOfMinute();
                $end = Carbon::parse($date . ' ' . $endBreak, 'Asia/Riyadh')->startOfMinute();
            } catch (\Throwable $e) {
                continue;
            }

            if ($end->lte($start)) {
                continue;
            }

            $normalized[] = [
                'start' => $start,
                'end' => $end,
            ];
        }

        return $normalized;
    }

    private function resolveWeeklyRotatingShiftId($baseShiftId, string $date, ?int $employeeId = null)
    {
        return app(\App\Services\EmployeeShiftSchedule::class)->resolve($baseShiftId, $date, $employeeId);
    }


    public function storePackageBooking(Request $request)
    {
        $data = $request->validate([
            'package_id'  => 'required|integer',
            'branch_id'   => 'required|integer|exists:branches,id',
            'date'        => 'required|date|after_or_equal:today',
            'time'        => 'required|string',
            'employye_id' => 'required|integer',
            'notes'       => 'nullable|string|max:1000',
            'total_price' => 'required|numeric|min:0',
        ]);

        if (!auth()->check()) {
            session(['package_booking' => $data]);
            return response()->json(['status' => 'guest']);
        }

        return $this->completePackageBooking($data);
    }

    public function completePackageBooking(array $data = null)
    {
        $data = $data ?? session('package_booking');
    
        if (!$data) {
            return redirect()->route('frontend.Packages');
        }
    
        DB::beginTransaction();
    
        try {
            $this->persistPackageBooking($data);
            DB::commit();
            session()->forget('package_booking');
    
            if (request()->expectsJson()) {
                return response()->json(['status' => 'saved']);
            }
    
            return redirect()
                ->route('frontend.Packages')
                ->with('success', 'تم حفظ الحجز بنجاح');
    
        } catch (\Throwable $e) {
            DB::rollBack();
        
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(), 
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ], 500);
        }
    }
    
    private function persistPackageBooking(array $data): void
    {
    
            if (!User::role('employee')->active()->whereKey($data['employye_id'])->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['employye_id' => 'الموظف المختار موقوف أو غير متاح.']);
            }
            $booking = Booking::create([
                'status'          => 'pending',
                'start_date_time' => $data['date'].' '.$data['time'],
                'user_id'         => auth()->id(),
                'branch_id'       => $data['branch_id'],
                'note'            => $data['notes'] ?? null,
                'created_by'      => auth()->id(),
            ]);
            $package = Package::findOrFail($data['package_id']);
            $packagePrice = $package->package_price;
            $employeeId = $data['employye_id']; 
            
            BookingPackages::create([
                'booking_id'   => $booking->id,
                'package_id'   => $data['package_id'],
                'employee_id'   => $employeeId,
                'user_id'      => auth()->id(),
                'package_price'  => $packagePrice,
                'created_by'    => auth()->id(),
            ]);
            
            UserPackage::create([
                'booking_id'   => $booking->id,
                'employee_id'        => $employeeId,
                'user_id'      => auth()->id(),
                'package_price'  => $packagePrice,
                'purchase_date'    => now(),
                'package_id'   => $data['package_id'],
            ]);
    }

/*-----------------------Helper function to filter time---------------------------*/
    function filterAvailableTimes($availableTimes, $serviceDuration) {
        $filtered = [];
        $serviceDuration = max(1, (int) $serviceDuration);

        if (empty($availableTimes)) {
            return $filtered;
        }

        // Scan continuous minute runs once instead of reparsing every candidate minute.
        $runEnd = null;
        $runLength = 0;
        foreach (array_reverse($availableTimes) as $time) {
            [$hour, $minute] = array_map('intval', explode(':', $time));
            $value = $hour * 60 + $minute;
            $runLength = $runEnd !== null && ($runEnd - $value + 1440) % 1440 === 1 ? $runLength + 1 : 1;
            $runEnd = $value;
            if ($runLength >= $serviceDuration) {
                $filtered[] = $time;
            }
        }
        $filtered = array_reverse($filtered);

        return $filtered;
    }
    /*-----------------------Helper function to filter time---------------------------*/
    function filterAvailableTimesNotConf($availableTimes, $bookedTimes, $serve_book_min) {
        $result = [];
    
        foreach ($availableTimes as $time) {
            $start = strtotime($time);
            $end   = $start + ($serve_book_min * 60);
    
            $conflict = false;
    
            foreach ($bookedTimes as $booked) {
                $bookedTimestamp = strtotime($booked);
    
                if ($bookedTimestamp >= $start && $bookedTimestamp < $end) {
                    $conflict = true;
                    break;
                }
            }
    
            if (!$conflict) {
                $result[] = $time;
            }
        }
    
        return $result;
    }

}
