<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use App\Models\BookingReview;
use App\Services\ReviewReport;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Employee\Models\EmployeeRating;

class ReviewReportController extends Controller
{
    public function __construct() { $this->middleware('permission:overall_booking_report'); }
    public function index(Request $request)
    {
        $filters=$request->validate([
            'from'=>['nullable','date_format:Y-m-d'],
            'to'=>['nullable','date_format:Y-m-d', ...($request->filled('from') ? ['after_or_equal:from'] : [])],
            'type'=>['nullable','in:branch,service,employee,booking'], 'rating'=>['nullable','integer','between:1,5'],
            'branch_id'=>['nullable','integer'], 'approval'=>['nullable','in:approved,pending'], 'visibility'=>['nullable','in:visible,hidden'],
        ]);
        $reviews=BookingReview::with(['user'=>fn($q)=>$q->withTrashed(), 'booking'=>fn($q)=>$q->withTrashed()->with(['branch','services.employee'])]);
        $legacy=EmployeeRating::with(['user'=>fn($q)=>$q->withTrashed(), 'employee'=>fn($q)=>$q->withTrashed(), 'booking'=>fn($q)=>$q->withTrashed()->with('branch')]);
        foreach ([$reviews,$legacy] as $query) {
            if (!empty($filters['from'])) $query->where('created_at','>=',$filters['from'].' 00:00:00');
            if (!empty($filters['to'])) $query->where('created_at','<=',$filters['to'].' 23:59:59');
        }
        $all=collect(ReviewReport::filter(ReviewReport::rows($reviews->get()->toArray(),$legacy->get()->toArray()),$filters))->sortByDesc('date')->values();
        $stats=[];
        foreach (['branch','service','employee','booking'] as $type) {
            $group=$all->where('type',$type); $stats[$type]=['count'=>$group->count(),'average'=>$group->avg('rating')];
        }
        $page=LengthAwarePaginator::resolveCurrentPage();
        $rows=new LengthAwarePaginator($all->forPage($page,30)->values(),$all->count(),30,$page,['path'=>$request->url(),'query'=>$request->query()]);
        $branches=DB::table('branches')->orderBy('id')->get(['id','name']);
        return view('backend.reports.reviews',compact('rows','stats','branches'));
    }
}
