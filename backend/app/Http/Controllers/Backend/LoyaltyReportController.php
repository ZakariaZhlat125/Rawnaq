<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use App\Models\LoyaltyPointTransaction;
use Illuminate\Http\Request;

class LoyaltyReportController extends Controller
{
    public function __construct() { $this->middleware('permission:view_loyalty'); }
    public function index(Request $request)
    {
        $filters=$request->validate([
            'from'=>['nullable','date_format:Y-m-d'],
            'to'=>['nullable','date_format:Y-m-d', ...($request->filled('from') ? ['after_or_equal:from'] : [])],
            'action'=>['nullable','in:add,deduct'], 'search'=>['nullable','string','max:100'],
        ]);
        $query=LoyaltyPointTransaction::with(['user'=>fn($q)=>$q->withTrashed()]);
        if(!empty($filters['from'])) $query->where('created_at','>=',$filters['from'].' 00:00:00');
        if(!empty($filters['to'])) $query->where('created_at','<=',$filters['to'].' 23:59:59');
        if(!empty($filters['action'])) $query->where('action',$filters['action']);
        if(!empty($filters['search'])) {
            $search='%'.$filters['search'].'%';
            $query->whereHas('user',fn($q)=>$q->withTrashed()->where(fn($u)=>$u->where('first_name','like',$search)->orWhere('last_name','like',$search)->orWhere('mobile','like',$search)->orWhere('email','like',$search)));
        }
        $added=(clone $query)->where('action','add')->sum('points');
        $used=(clone $query)->where('action','deduct')->sum('points');
        $users=(clone $query)->distinct()->count('user_id');
        $rows=$query->orderByDesc('created_at')->orderByDesc('id')->paginate(30)->withQueryString();
        return view('backend.reports.loyalty',compact('rows','added','used','users'));
    }
}
