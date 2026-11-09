<?php

namespace Modules\Employee\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmployeePerformanceReport;
use App\Services\EmployeeReviewSummary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\BussinessHour\Models\Shift;
use Modules\Employee\Models\BranchEmployee;

class EmployeePerformanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_staff')->only('show');
        $this->middleware('permission:edit_staff')->only('schedule');
    }

    public function show(Request $request, $id)
    {
        $employee = User::select('users.*')->role('employee')->branch()->findOrFail($id);
        $dates = $request->validate(['from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from']]);
        $from = $dates['from'] ?? now('Asia/Riyadh')->startOfMonth()->toDateString();
        $to = $dates['to'] ?? now('Asia/Riyadh')->toDateString();
        abort_if($to < $from, 422, 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية.');
        $report = app(EmployeePerformanceReport::class)->build($employee->id, $from, $to);
        $votes = collect(app(EmployeeReviewSummary::class)->votes([$employee->id]));
        $reviewStats = ['average' => $votes->avg('rating'), 'count' => $votes->count()];
        $votes = $votes->filter(fn ($v) => substr((string) $v['date'], 0, 10) >= $from && substr((string) $v['date'], 0, 10) <= $to)->sortByDesc('date');
        $assignment = $employee->branch;
        $shifts = Shift::where('status', 1)->orderBy('id')->get();
        return view('employee::backend.employees.performance', compact('employee', 'report', 'reviewStats', 'votes', 'assignment', 'shifts', 'from', 'to'));
    }

    public function schedule(Request $request, $id)
    {
        $employee = User::select('users.*')->role('employee')->branch()->findOrFail($id);
        $data = $request->validate([
            'shift_id' => ['required', 'integer', \Illuminate\Validation\Rule::exists('shifts', 'id')->where('status', 1)],
            'shift_rotation_enabled' => ['required', 'boolean'],
            'shift_rotation_anchor' => ['required', 'date_format:Y-m-d'],
        ]);
        DB::transaction(function () use ($employee, $data) {
            $assignment = BranchEmployee::where('employee_id', $employee->id)->lockForUpdate()->firstOrFail();
            $assignment->update($data);
        });
        return back()->with('success', 'تم تغيير وردية الموظف. تسري على المواعيد المتاحة الجديدة ولا تغيّر الحجوزات القائمة.');
    }
}
