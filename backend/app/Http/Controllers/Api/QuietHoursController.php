<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BranchQuietHours;
use App\Services\QuietHoursRule;

class QuietHoursController extends Controller
{
    public function __invoke()
    {
        if (!app(\App\Services\QuietHoursRewardService::class)->available()) {
            return response()->json(['status' => true, 'data' => [], 'timezone' => QuietHoursRule::TIMEZONE])->header('Cache-Control', 'no-store');
        }
        return response()->json(['status' => true, 'data' => BranchQuietHours::where('enabled', true)
            ->get(['branch_id', 'enabled', 'points', 'hours'])->values(), 'timezone' => QuietHoursRule::TIMEZONE])
            ->header('Cache-Control', 'no-store');
    }
}
