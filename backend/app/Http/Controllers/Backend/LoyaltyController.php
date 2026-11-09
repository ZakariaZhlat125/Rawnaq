<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;

class LoyaltyController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_loyalty')->only('index');
        $this->middleware('permission:store_loyalty')->only('store');
    }
    
    public function index()
    {
        $points_per_100 = Setting::get('points_per_100');
        $point_value = Setting::get('point_value');
        $referral_points = Setting::get('referral_points', 0);
        $referral_new_user_points = Setting::get('referral_new_user_points', $referral_points);

        return view('backend.loyalty.index' , compact('points_per_100','point_value','referral_points','referral_new_user_points'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'points_per_100' => 'required|numeric|min:1',
            'referral_points' => 'required|integer|min:0|max:1000000',
            'referral_new_user_points' => 'required|integer|min:0|max:1000000',
            'point_value'    => 'required|numeric|min:0.01',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
            Setting::set('points_per_100', $request->points_per_100);
            Setting::set('point_value', $request->point_value);
            Setting::set('referral_points', $request->referral_points);
            Setting::set('referral_new_user_points', $request->referral_new_user_points);
        });
    
        return redirect()->back()->with('success', __('messages.success_save_loyalty'));
    }

    
}







