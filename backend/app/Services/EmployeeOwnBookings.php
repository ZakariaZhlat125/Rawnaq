<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class EmployeeOwnBookings {
    /** Scope before union, pagination, filtering and totals. No caller-supplied employee IDs. */
    public function query(int $employeeId, string $from, string $to) {
        $base=fn($table)=>DB::table($table.' as line')->join('bookings as b','b.id','=','line.booking_id')
            ->leftJoin('users as customer','customer.id','=','b.user_id')
            ->where('line.employee_id',$employeeId)->whereNull('b.deleted_at')
            ->where('b.start_date_time','>=',$from.' 00:00:00')->where('b.start_date_time','<=',$to.' 23:59:59');
        $services=$base('booking_services')->leftJoin('services as item','item.id','=','line.service_id')->select([
            'b.id as booking_id','b.status','customer.first_name','customer.last_name','item.name',
            DB::raw('COALESCE(line.start_date_time,b.start_date_time) as starts_at'),
            DB::raw("'service' as kind"),'line.duration_min',DB::raw('COALESCE(line.service_price,0)-COALESCE(line.discount_amount,0) as amount'),
        ]);
        $packages=$base('booking_packages')->leftJoin('packages as item','item.id','=','line.package_id')->select([
            'b.id as booking_id','b.status','customer.first_name','customer.last_name','item.name','b.start_date_time as starts_at',
            DB::raw("'package' as kind"),DB::raw('0 as duration_min'),DB::raw('COALESCE(line.package_price,0) as amount'),
        ]);
        return DB::query()->fromSub($services->unionAll($packages),'mine');
    }
    public function report(int $employeeId,string $from,string $to): array {
        $query=$this->query($employeeId,$from,$to);
        return [
            'bookings'=>(clone $query)->distinct()->count('booking_id'),
            'completed'=>(clone $query)->where('status','completed')->distinct()->count('booking_id'),
            'cancelled'=>(clone $query)->whereIn('status',['cancelled','canceled'])->distinct()->count('booking_id'),
            'completed_value'=>round((float)(clone $query)->where('status','completed')->sum('amount'),2),
            'commission'=>round((float)DB::table('commission_earnings')->where('employee_id',$employeeId)
                ->whereBetween('created_at',[$from.' 00:00:00',$to.' 23:59:59'])->sum('commission_amount'),2),
        ];
    }
    public static function name($value): string {
        $names=json_decode((string)$value,true);
        return is_array($names) ? (string)($names[app()->getLocale()] ?? $names['ar'] ?? $names['en'] ?? '') : (string)$value;
    }
}
