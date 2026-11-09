<?php

namespace Modules\Affiliate\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Modules\Affiliate\Models\Affiliate;

class AffiliateController extends Controller
{
    public function dashboard(Request $request)
    {
        $affiliate = Affiliate::where('user_id', auth()->id())->firstOrFail();

        // --------- STATISTICS ----------
        $totalVisitors = $affiliate->visitors()->count();
        $totalConversions = $affiliate->conversions()->count();
        $totalEarnings = $affiliate->wallet_total;
        $availableEarnings = $affiliate->wallet_available;

        // --------- TOP LINKS ----------
        // $topLinks = $affiliate->visitors()
        //     ->selectRaw('ref_url, COUNT(*) as total')
        //     ->groupBy('ref_url')
        //     ->orderByDesc('total')
        //     ->limit(5)
        //     ->get();

        // --------- CHART: LAST 30 DAYS ----------
        $stats = $affiliate->visitors()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as visitors')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('day')
            ->pluck('visitors', 'day');

        // Ensure full range of dates
        $chartLabels = [];
        $chartData = [];

        for ($i = 30; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $chartLabels[] = $date;
            $chartData[] = $stats[$date] ?? 0;
        }

        // --------- LAST CONVERSIONS ----------
        $lastConversions = $affiliate->conversions()
            ->latest()
            ->limit(5)
            ->get();

        $referralLink = route('affiliate.track', ['ref_code' => $affiliate->ref_code]);

        return view('affiliate::dashboard', compact(
            'affiliate',
            'totalVisitors',
            'totalConversions',
            'totalEarnings',
            'availableEarnings',
            // 'topLinks',
            'chartLabels',
            'chartData',
            'lastConversions',
            'referralLink'
        ));
    }

    public function stats()
    {
        $affiliate = Affiliate::where('user_id', auth()->id())->firstOrFail();

        // --------- VISITORS BY DAY (LAST 30 DAYS) ----------
        $visitorsStats = $affiliate->visitors()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as visitors')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('day')
            ->pluck('visitors', 'day');

        $visitorLabels = [];
        $visitorData = [];

        for ($i = 30; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $visitorLabels[] = $date;
            $visitorData[] = $visitorsStats[$date] ?? 0;
        }

        // --------- CONVERSIONS BY DAY (LAST 30 DAYS) ----------
        $conversionsStats = $affiliate->conversions()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as conversions')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('day')
            ->pluck('conversions', 'day');

        $conversionData = [];
        for ($i = 30; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $conversionData[] = $conversionsStats[$date] ?? 0;
        }

        return view('affiliate::stats', compact(
            'affiliate',
            'visitorLabels',
            'visitorData',
            'conversionData'
        ));
    }

    public function conversions()
    {
        $affiliate = Affiliate::where('user_id', auth()->id())->firstOrFail();

        $conversions = $affiliate->conversions()
            ->latest()
            ->paginate(20);

        return view('affiliate::conversions', compact('affiliate', 'conversions'));
    }
}
