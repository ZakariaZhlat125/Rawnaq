<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReferralReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:overall_booking_report');
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'from'=>['nullable','date_format:Y-m-d'],
            'to'=>['nullable','date_format:Y-m-d', ...($request->filled('from') ? ['after_or_equal:from'] : [])],
            'search'=>['nullable','string','max:100'],
        ]);
        // Query-builder joins preserve historical registrations and deleted referrers.
        $query = DB::table('users as member')->leftJoin('users as owner', 'owner.id', '=', 'member.referred_by_user_id')
            ->whereNotNull('member.referred_by_user_id')->whereNotNull('member.referral_rewarded_at')
            ->select(['member.id', 'member.first_name', 'member.last_name', 'member.mobile', 'member.created_at',
                'member.referral_rewarded_at', 'member.registration_referral_code', 'member.referral_owner_points', 'member.referral_member_points',
                'owner.id as owner_id', 'owner.first_name as owner_first_name', 'owner.last_name as owner_last_name',
                'owner.mobile as owner_mobile', 'owner.referral_code as owner_code'])
            ->selectSub(DB::table('loyalty_points_transactions')->selectRaw('COALESCE(SUM(points), 0)')
                ->where('source', 'referral')->where('action', 'add')->whereColumn('source_id', 'member.id')
                ->whereColumn('user_id', 'member.referred_by_user_id'), 'legacy_owner_points');
        if (!empty($filters['from'])) $query->where('member.created_at', '>=', $filters['from'].' 00:00:00');
        if (!empty($filters['to'])) $query->where('member.created_at', '<=', $filters['to'].' 23:59:59');
        if (!empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($search) {
                foreach (['member.first_name','member.last_name','member.mobile','member.registration_referral_code','owner.first_name','owner.last_name','owner.mobile','owner.referral_code'] as $column) $q->orWhere($column,'like',$search);
            });
        }
        $total = (clone $query)->count();
        $ownerPoints = (clone $query)->sum(DB::raw('COALESCE(member.referral_owner_points, (SELECT COALESCE(SUM(points),0) FROM loyalty_points_transactions WHERE source = \'referral\' AND action = \'add\' AND source_id = member.id AND user_id = member.referred_by_user_id))'));
        $memberPoints = (clone $query)->sum('member.referral_member_points');
        $rows = $query->orderByDesc('member.created_at')->orderByDesc('member.id')->paginate(25)->withQueryString();
        return view('backend.reports.referrals', compact('rows','total','ownerPoints','memberPoints'));
    }
}
