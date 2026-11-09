<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use App\Services\EmployeeOwnBookings;
use Illuminate\Http\Request;
class EmployeePortalController extends Controller {
    public function __construct() { $this->middleware(['auth','role:employee']); }
    private function period(Request $request): array {
        $data=$request->validate(['from'=>['nullable','date_format:Y-m-d'],'to'=>['nullable','date_format:Y-m-d','after_or_equal:from']]);
        $from=$data['from'] ?? now()->startOfMonth()->toDateString();$to=$data['to'] ?? now()->endOfMonth()->toDateString();
        abort_if($from>$to || \Carbon\Carbon::parse($from)->diffInDays($to)>366,422,__('employee_portal.invalid_period'));
        return [$from,$to];
    }
    public function bookings(Request $request,EmployeeOwnBookings $service) {
        [$from,$to]=$this->period($request);
        $bookings=$service->query((int)$request->user()->id,$from,$to)->orderBy('starts_at')->orderBy('booking_id')->paginate(25)->withQueryString();
        $bookings->getCollection()->transform(function($row){$row->name=EmployeeOwnBookings::name($row->name);return $row;});
        if ($request->is('api/*') || $request->expectsJson()) return response()->json($bookings)->header('Cache-Control','private, no-store');
        $report=null;return response()->view('backend.employee.portal',compact('bookings','report','from','to'))->header('Cache-Control','private, no-store');
    }
    public function reports(Request $request,EmployeeOwnBookings $service) {
        [$from,$to]=$this->period($request);
        $report=$service->report((int)$request->user()->id,$from,$to);
        if ($request->is('api/*') || $request->expectsJson()) return response()->json(['data'=>$report])->header('Cache-Control','private, no-store');
        $bookings=null;return response()->view('backend.employee.portal',compact('bookings','report','from','to'))->header('Cache-Control','private, no-store');
    }
}
