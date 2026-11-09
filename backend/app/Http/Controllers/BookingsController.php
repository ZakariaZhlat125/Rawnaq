<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\World\Models\State;
use Modules\Product\Models\Product;

class BookingsController extends Controller
{
    public function salon(Request $request){
        return view('frontend.bookings.salon-booking.create', $this->salonPageData($request));
    }


    private function salonPageData(Request $request): array
    {
        $b = $request->query('branch');
        $States = State::where('status', 1)->get();
        $first_States = State::where('status', 1)->first();
        $suggest = Product::with(['media', 'categories'])
            ->where('status', 1)
            ->where('is_featured', 1)
            ->where('deleted_at', null)
            ->take(3)
            ->get();
        $setting = DB::table('settings')->where('name', 'service_duration_visibility')->first();
        $showDuration = $setting ? (bool) $setting->val : true;

        return compact('States', 'b', 'suggest', 'first_States', 'showDuration');
    }
}
