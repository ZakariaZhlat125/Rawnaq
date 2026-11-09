<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchQuietHours;
use Illuminate\Http\Request;

class QuietHoursController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_loyalty')->only('index');
        $this->middleware('permission:store_loyalty')->only('store');
    }

    public function index(Request $request)
    {
        abort_unless(app(\App\Services\QuietHoursRewardService::class)->available(), 503, __('quiet_hours.migration_required'));
        $branches = Branch::orderBy('id')->get();
        $branchId = $request->integer('branch_id') ?: $branches->first()?->id;
        abort_unless($branches->contains('id', $branchId), 404);
        $settings = BranchQuietHours::firstOrNew(['branch_id' => $branchId]);
        return view('backend.quiet-hours.index', compact('branches', 'branchId', 'settings'));
    }

    public function store(Request $request)
    {
        abort_unless(app(\App\Services\QuietHoursRewardService::class)->available(), 503, __('quiet_hours.migration_required'));
        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'enabled' => ['required', 'boolean'],
            'points' => ['required', 'integer', 'min:1', 'max:1000000'],
            'hours' => ['nullable', 'array:0,1,2,3,4,5,6'],
            'hours.*' => ['array'],
            'hours.*.*' => ['integer', 'between:0,23'],
        ]);
        $hours = [];
        for ($day = 0; $day < 7; $day++) {
            $hours[$day] = array_values(array_unique(array_map('intval', $data['hours'][$day] ?? [])));
            sort($hours[$day]);
        }
        if ($request->boolean('enabled') && !array_filter($hours)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['hours' => __('quiet_hours.select_hours')]);
        }
        BranchQuietHours::updateOrCreate(['branch_id' => $data['branch_id']], [
            'enabled' => $request->boolean('enabled'), 'points' => $data['points'], 'hours' => $hours,
        ]);
        return redirect()->route('backend.quiet-hours.index', ['branch_id' => $data['branch_id']])
            ->with('success', __('quiet_hours.saved'));
    }
}
