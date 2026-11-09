<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class FrontendLoyaltyController extends Controller
{
    public function loyalety(Request $request)
    {
        $point_value = Setting::get('point_value');

        return view('frontend.loyalty.index', compact('point_value'));
    }
}
