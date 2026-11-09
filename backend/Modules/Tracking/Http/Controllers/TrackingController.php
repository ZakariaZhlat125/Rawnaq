<?php

namespace Modules\Tracking\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Affiliate\Models\Affiliate;
use Modules\Tracking\Models\Visitor;

class TrackingController extends Controller
{
    public function track(Request $request, $ref_code, $id = null)
    {
        $affiliate = Affiliate::where('ref_code', $ref_code)->first();

        if (!$affiliate) {
            return redirect('/');
        }

        if (auth()->check() && (int) auth()->id() === (int) $affiliate->user_id) {
            return redirect('/');
        }

        $token = session('affiliate_token');

        if (!$token) {
            $token = Str::uuid()->toString();
            session(['affiliate_token' => $token]);
        }
        session(['affiliate_id' => $affiliate->id]);

        Visitor::firstOrCreate(
            ['token' => $token],
            [
                'affiliate_id' => $affiliate->id,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );

        cookie()->queue(cookie('affiliate_token', $token, 60 * 24 * 30));

        $redirectTo = $id ? url('/product/' . $id) : url('/');
        return redirect()->to($redirectTo);
    }
}
