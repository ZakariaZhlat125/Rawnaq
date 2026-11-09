<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\EmployeeOccupancyReport;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmployeeOccupancyReportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $tables = [
            'users' => ['first_name:string', 'last_name:string', 'status:integer', 'is_banned:integer', 'is_manager:integer', 'deleted_at:timestamp'],
            'roles' => ['name:string', 'guard_name:string'],
            'model_has_roles' => ['role_id:integer', 'model_id:integer', 'model_type:string'],
            'branches' => ['name:text', 'deleted_at:timestamp'],
            'branch_employee' => ['branch_id:integer', 'employee_id:integer', 'shift_id:integer'],
            'staff_working_hours' => ['staff_id:integer', 'day_of_week:string', 'start_time:string', 'end_time:string', 'breaks:text', 'is_holiday:integer'],
            'bussinesshours' => ['branch_id:integer', 'shift_id:integer', 'day:string', 'start_time:string', 'end_time:string', 'breaks:text', 'is_holiday:integer', 'deleted_at:timestamp'],
            'staff_leave_periods' => ['staff_id:integer', 'start_date:date', 'end_date:date'],
            'holidays' => ['branch_id:integer', 'date:date', 'deleted_at:timestamp'],
            'bookings' => ['branch_id:integer', 'status:string', 'payment_type:string', 'start_date_time:datetime', 'deleted_at:timestamp'],
            'booking_services' => ['booking_id:integer', 'employee_id:integer', 'start_date_time:datetime', 'duration_min:integer'],
            'booking_transactions' => ['booking_id:integer', 'payment_status:integer'],
            'booking_packages' => ['booking_id:integer', 'employee_id:integer', 'package_id:integer'],
            'package_services' => ['package_id:integer', 'service_id:integer'],
            'services' => ['duration_min:integer'],
        ];
        foreach ($tables as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                $table->id();
                foreach ($columns as $column) {
                    [$name, $type] = explode(':', $column);
                    $table->$type($name)->nullable();
                }
            });
        }
        DB::table('roles')->insert(['id' => 1, 'name' => 'employee', 'guard_name' => 'web']);
        foreach ([1, 2] as $id) {
            DB::table('users')->insert(['id' => $id, 'first_name' => 'Employee '.$id, 'last_name' => '', 'status' => 1, 'is_banned' => 0, 'is_manager' => 0]);
            DB::table('model_has_roles')->insert(['role_id' => 1, 'model_id' => $id, 'model_type' => User::class]);
            DB::table('branches')->insert(['id' => $id, 'name' => json_encode(['en' => 'Branch '.$id, 'ar' => 'Branch '.$id])]);
            DB::table('branch_employee')->insert(['employee_id' => $id, 'branch_id' => $id, 'shift_id' => 1]);
            DB::table('bussinesshours')->insert(['branch_id' => $id, 'shift_id' => 1, 'day' => 'monday', 'start_time' => '09:00', 'end_time' => '17:00', 'is_holiday' => 0,
                'breaks' => json_encode([['start_break' => '12:00', 'end_break' => '13:00']])]);
        }
    }

    private function booking(int $id, string $time, int $duration, string $status = 'confirmed', ?string $paymentType = null): void
    {
        DB::table('bookings')->insert(['id' => $id, 'branch_id' => 1, 'status' => $status, 'payment_type' => $paymentType, 'start_date_time' => '2026-09-28 '.$time]);
        DB::table('booking_services')->insert(['booking_id' => $id, 'employee_id' => 1, 'start_date_time' => '2026-09-28 '.$time, 'duration_min' => $duration]);
    }

    public function test_branch_scope_overlap_cancellations_unpaid_carts_and_packages(): void
    {
        $this->booking(1, '10:00:00', 120);
        $this->booking(2, '11:00:00', 120, 'completed');
        $this->booking(3, '15:00:00', 60, 'cancelled');
        $this->booking(4, '15:00:00', 60, 'pending', 'cart');
        DB::table('bookings')->insert(['id' => 5, 'branch_id' => 1, 'status' => 'confirmed', 'start_date_time' => '2026-09-28 14:00:00']);
        DB::table('services')->insert(['id' => 1, 'duration_min' => 60]);
        DB::table('package_services')->insert(['package_id' => 1, 'service_id' => 1]);
        DB::table('booking_packages')->insert(['booking_id' => 5, 'employee_id' => 1, 'package_id' => 1]);
        $rows = app(EmployeeOccupancyReport::class)->forPeriod('2026-09-28', '2026-09-28', 1, 'Asia/Riyadh');
        $this->assertCount(1, $rows);
        $this->assertSame(1, $rows[0]['id']);
        $this->assertSame(420.0, $rows[0]['available_minutes']);
        $this->assertSame(180.0, $rows[0]['booked_minutes']);
        $this->assertSame(42.9, $rows[0]['percentage']);
        $this->assertSame(0.0, app(EmployeeOccupancyReport::class)->forPeriod('2026-09-28', '2026-09-28', 2, 'Asia/Riyadh')[0]['percentage']);
    }

    public function test_staff_schedule_precedence_leave_and_branch_holiday(): void
    {
        DB::table('staff_working_hours')->insert(['staff_id' => 1, 'day_of_week' => 'monday', 'start_time' => '10:00', 'end_time' => '14:00', 'is_holiday' => 0]);
        $report = app(EmployeeOccupancyReport::class);
        $this->assertSame(240.0, $report->forPeriod('2026-09-28', '2026-09-28', 1, 'Asia/Riyadh')[0]['available_minutes']);
        DB::table('staff_leave_periods')->insert(['staff_id' => 1, 'start_date' => '2026-09-28', 'end_date' => '2026-09-28']);
        $this->assertNull($report->forPeriod('2026-09-28', '2026-09-28', 1, 'Asia/Riyadh')[0]['percentage']);
        DB::table('holidays')->insert(['branch_id' => 2, 'date' => '2026-09-28']);
        $this->assertNull($report->forPeriod('2026-09-28', '2026-09-28', 2, 'Asia/Riyadh')[0]['percentage']);
    }

    public function test_shared_shift_capacity_is_not_doubled_and_missing_schedule_is_unavailable(): void
    {
        DB::table('branch_employee')->insert(['employee_id' => 1, 'branch_id' => 2, 'shift_id' => 1]);
        $report = app(EmployeeOccupancyReport::class);
        $rows = $report->forPeriod('2026-09-28', '2026-09-28', null, 'Asia/Riyadh');
        $this->assertCount(2, $rows);
        $this->assertSame(420.0, $rows[0]['available_minutes']);
        $this->assertNull($report->forPeriod('2026-09-29', '2026-09-29', 1, 'Asia/Riyadh')[0]['percentage']);
    }

    public function test_previous_night_shift_is_clipped_and_does_not_start_on_a_holiday(): void
    {
        DB::table('bussinesshours')->where('branch_id', 1)->delete();
        DB::table('bussinesshours')->insert(['branch_id' => 1, 'shift_id' => 1, 'day' => 'sunday', 'start_time' => '22:00', 'end_time' => '02:00', 'is_holiday' => 0,
            'breaks' => json_encode([['start_break' => '00:30', 'end_break' => '01:00']])]);
        $this->booking(1, '00:00:00', 120);
        $report = app(EmployeeOccupancyReport::class);
        $row = $report->forPeriod('2026-09-28', '2026-09-28', 1, 'Asia/Riyadh')[0];
        $this->assertSame(90.0, $row['available_minutes']);
        $this->assertSame(90.0, $row['booked_minutes']);
        $this->assertSame(100.0, $row['percentage']);
        DB::table('holidays')->insert(['branch_id' => 1, 'date' => '2026-09-27']);
        $this->assertNull($report->forPeriod('2026-09-28', '2026-09-28', 1, 'Asia/Riyadh')[0]['percentage']);
    }

    public function test_dashboard_partial_renders_percentage_and_unavailable_state(): void
    {
        $html = view('backend.includes.employee_occupancy', ['date_range' => '2026-09-28 to 2026-09-28', 'data' => ['employee_occupancy' => [
            ['id' => 1, 'name' => '<script>bad</script>', 'branches' => 'Branch 1', 'booked_minutes' => 180, 'available_minutes' => 420, 'percentage' => 42.9],
            ['id' => 2, 'name' => 'Employee 2', 'branches' => 'Branch 2', 'booked_minutes' => 0, 'available_minutes' => 0, 'percentage' => null],
        ]]])->render();
        $this->assertStringContainsString('42.9%', $html);
        $this->assertStringContainsString('aria-valuenow="42.9"', $html);
        $this->assertStringNotContainsString('<script>bad</script>', $html);
        $this->assertStringContainsString(__('dashboard.occupancy_unavailable'), $html);
    }
}
