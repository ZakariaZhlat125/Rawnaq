<?php

namespace App\Services;

use App\Models\StaffLeavePeriod;
use App\Models\StaffWorkingHour;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\BussinessHour\Models\BussinessHour;
use Modules\Holiday\Models\Holiday;

class EmployeeOccupancyReport
{
    public function __construct(private EmployeeOccupancyCalculator $calculator) {}

    public function forPeriod(string $from, string $to, ?int $branchId, string $timezone): array
    {
        $start = CarbonImmutable::parse($from, $timezone)->startOfDay();
        $end = CarbonImmutable::parse($to, $timezone)->startOfDay()->addDay();
        $employees = User::employee()->active()->where('is_manager', 0)
            ->whereHas('branches', fn ($q) => $q->when($branchId !== null, fn ($q) => $q->where('branch_id', $branchId)))
            ->with(['branches' => fn ($q) => $q->when($branchId !== null, fn ($q) => $q->where('branch_id', $branchId))->with('getBranch')])
            ->orderBy('first_name')->get();
        if ($employees->isEmpty()) return [];

        $ids = $employees->pluck('id')->all();
        $branchIds = $employees->flatMap(fn ($employee) => $employee->branches->pluck('branch_id'))->unique()->all();
        $staffHours = StaffWorkingHour::whereIn('staff_id', $ids)->orderByDesc('id')->get()
            ->unique(fn ($hour) => $hour->staff_id.'|'.$hour->day_of_week)
            ->keyBy(fn ($hour) => $hour->staff_id.'|'.$hour->day_of_week);
        $branchHours = BussinessHour::whereIn('branch_id', $branchIds)->orderByDesc('id')->get()->groupBy('branch_id');
        $leaves = StaffLeavePeriod::whereIn('staff_id', $ids)->whereDate('start_date', '<', $end->toDateString())
            ->whereDate('end_date', '>=', $start->subDay()->toDateString())->get()->groupBy('staff_id');
        $holidays = Holiday::whereIn('branch_id', $branchIds)->whereBetween('date', [$start->subDay()->toDateString(), $end->toDateString()])
            ->get()->groupBy('branch_id');
        $booked = $this->bookedIntervals($ids, $branchId, $start, $end, $timezone);
        $rows = [];
        foreach ($employees as $employee) {
            $working = [];
            $excluded = [];
            $employeeLeaves = $leaves->get($employee->id, collect());
            foreach ($employeeLeaves as $leave) {
                $excluded[] = [CarbonImmutable::parse($leave->start_date->toDateString(), $timezone)->timestamp,
                    CarbonImmutable::parse($leave->end_date->toDateString(), $timezone)->addDay()->timestamp];
            }
            foreach ($employee->branches as $assignment) {
                $branchRanges = [];
                $branchHolidays = $holidays->get($assignment->branch_id, collect());
                // Include the previous night's shift and clip it to the selected period.
                for ($day = $start->subDay(); $day->lessThan($end); $day = $day->addDay()) {
                    $date = $day->toDateString();
                    // A shift cannot start on leave/closure and resume after midnight.
                    if ($branchHolidays->contains(fn ($holiday) => substr((string) $holiday->date, 0, 10) === $date)
                        || $employeeLeaves->contains(fn ($leave) => $leave->start_date->toDateString() <= $date && $leave->end_date->toDateString() >= $date)) {
                        continue;
                    }
                    $weekday = strtolower($day->format('l'));
                    $config = $staffHours->get($employee->id.'|'.$weekday);
                    if (!$config) {
                        $config = $branchHours->get($assignment->branch_id, collect())->first(fn ($hour) =>
                            $hour->day === $weekday && (!$assignment->shift_id || (int) $hour->shift_id === (int) $assignment->shift_id));
                    }
                    if ($config) {
                        $branchRanges = array_merge($branchRanges, $this->calculator->workingRanges($day->toDateString(), $config->toArray(), $timezone));
                    }
                }
                $branchClosures = [];
                foreach ($branchHolidays as $holiday) {
                    $day = CarbonImmutable::parse($holiday->date, $timezone)->startOfDay();
                    $branchClosures[] = [$day->timestamp, $day->addDay()->timestamp];
                }
                $working = array_merge($working, $this->calculator->subtract($branchRanges, $branchClosures));
            }
            $working = $this->calculator->subtract($working, $excluded);
            $rows[] = array_merge([
                'id' => $employee->id,
                'name' => trim($employee->first_name.' '.$employee->last_name),
                'branches' => $employee->branches->map(fn ($assignment) => $assignment->getBranch?->name)->filter()->unique()->implode('، '),
            ], $this->calculator->calculate($working, $booked[$employee->id] ?? [], $start->timestamp, $end->timestamp));
        }
        usort($rows, fn ($a, $b) => ($b['percentage'] ?? -1) <=> ($a['percentage'] ?? -1));
        return $rows;
    }

    private function bookedIntervals(array $ids, ?int $branchId, CarbonImmutable $start, CarbonImmutable $end, string $timezone): array
    {
        $packageDurations = DB::table('package_services')->join('services', 'services.id', '=', 'package_services.service_id')
            ->select('package_services.package_id')->selectRaw('SUM(services.duration_min) as duration_min')->groupBy('package_services.package_id');
        $sources = [
            [DB::table('booking_services'), 'booking_services', 'COALESCE(booking_services.start_date_time, bookings.start_date_time)', 'booking_services.duration_min'],
            [DB::table('booking_packages')->joinSub($packageDurations, 'package_durations', fn ($join) => $join->on('package_durations.package_id', '=', 'booking_packages.package_id')),
                'booking_packages', 'bookings.start_date_time', 'package_durations.duration_min'],
        ];
        $result = [];
        foreach ($sources as [$query, $table, $time, $duration]) {
            $finish = DB::connection()->getDriverName() === 'sqlite'
                ? "datetime($time, '+' || $duration || ' minutes')"
                : "DATE_ADD($time, INTERVAL $duration MINUTE)";
            $rows = $query->join('bookings', 'bookings.id', '=', $table.'.booking_id')
                ->whereNull('bookings.deleted_at')->whereNotIn('bookings.status', ['cancelled', 'canceled'])
                ->whereIn($table.'.employee_id', $ids)
                ->when($branchId !== null, fn ($q) => $q->where('bookings.branch_id', $branchId))
                ->where(function ($q) {
                    $q->whereNull('bookings.payment_type')->orWhere('bookings.payment_type', '!=', 'cart')
                        ->orWhereExists(fn ($paid) => $paid->selectRaw('1')->from('booking_transactions')
                            ->whereColumn('booking_transactions.booking_id', 'bookings.id')->where('payment_status', 1));
                })
                ->whereRaw("$time < ?", [$end->toDateTimeString()])
                ->whereRaw("$finish > ?", [$start->toDateTimeString()])
                ->where($duration, '>', 0)
                ->select($table.'.employee_id')->selectRaw("$time as starts_at, $duration as duration_min")->get();
            foreach ($rows as $row) {
                $from = CarbonImmutable::parse($row->starts_at, $timezone);
                $result[$row->employee_id][] = [$from->timestamp, $from->addMinutes((int) $row->duration_min)->timestamp];
            }
        }
        return $result;
    }
}
