<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\GiftCard;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GiftReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_gift_reports');
    }

    public function index(Request $request)
    {
        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->to)->endOfDay() : now()->endOfDay();
        // Face value: value cards their amount, service/package gifts their recorded price.
        $face = 'CASE WHEN type = \'value\' THEN COALESCE(value_amount, 0) ELSE COALESCE(subtotal, 0) END';

        $sales = GiftCard::where('source', 'customer')->where('payment_status', 1)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("type, COUNT(*) c, COALESCE(SUM($face), 0) total")
            ->groupBy('type')->get()->keyBy('type');

        $issued = GiftCard::where('source', '!=', 'customer')->where('payment_status', 1)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("source, COUNT(*) c, COALESCE(SUM($face), 0) total")
            ->groupBy('source')->get()->keyBy('source');

        $redeemed = GiftCard::whereNotNull('redeemed_at')->whereBetween('redeemed_at', [$from, $to])
            ->selectRaw("COUNT(*) c, COALESCE(SUM($face), 0) total")->first();

        // Liability today: live gifts nobody has used yet.
        $outstanding = GiftCard::whereIn('gift_status', GiftCard::USABLE_STATUSES)->whereNull('redeemed_at')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->selectRaw("type, COUNT(*) c, COALESCE(SUM($face), 0) total")
            ->groupBy('type')->get()->keyBy('type');

        $expired = GiftCard::where('gift_status', GiftCard::STATUS_EXPIRED)
            ->whereBetween('expires_at', [$from, $to])
            ->selectRaw("COUNT(*) c, COALESCE(SUM($face), 0) total")->first();

        $cancelled = GiftCard::whereIn('gift_status', [GiftCard::STATUS_CANCELLED, GiftCard::STATUS_REFUNDED])
            ->whereBetween('cancelled_at', [$from, $to])
            ->selectRaw("COUNT(*) c, COALESCE(SUM($face), 0) total")->first();

        $byOccasion = DB::table('gift_cards')
            ->leftJoin('gift_designs', 'gift_designs.slug', '=', 'gift_cards.design')
            ->where('gift_cards.payment_status', 1)
            ->whereBetween('gift_cards.created_at', [$from, $to])
            ->selectRaw("COALESCE(gift_designs.occasion, 'general') occasion, COUNT(*) c")
            ->groupBy('occasion')->orderByDesc('c')->get();

        $funnel = GiftCard::where('payment_status', 1)->whereBetween('created_at', [$from, $to])
            ->selectRaw('COUNT(*) total, COUNT(sent_at) sent, COUNT(opened_at) opened, COUNT(claimed_at) claimed, COUNT(redeemed_at) redeemed')
            ->first();

        return view('backend.gift.report', compact('from', 'to', 'sales', 'issued', 'redeemed', 'outstanding', 'expired', 'cancelled', 'byOccasion', 'funnel'));
    }
}
