<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Promotion\Models\Coupon;

class CouponController extends Controller
{
    public function availableCoupons()
    {
        $coupons = Coupon::with('promotion')->where('is_expired', 0)->where('use_limit', '>=', 1)->whereDate('start_date_time', '<=', now('Asia/Riyadh')->toDateString())->whereDate('end_date_time', '>=', now('Asia/Riyadh')->toDateString())->whereNull('deleted_at')->whereHas('promotion', fn ($q) => $q->where('status', 1))->get();
        
        return response()->json([
            'status' => true,
            'data' => $coupons,
            'message' => __('promotion.coupons_list'),
        ], 200);
    }
}
