<?php
namespace App\Http\Middleware;
use App\Services\EmployeeAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class EmployeeOnlyAccess {
    public function handle(Request $request, Closure $next) {
        $user=$request->user();
        if (!$user && $request->is('api/*') && $request->bearerToken()) $user=Auth::guard('sanctum')->user();
        if (!EmployeeAccess::limited($user)) return $next($request);
        $name=$request->route()?->getName();
        // Explicit allowlist, so new management endpoints are denied by default.
        $allowed=['backend.employee.bookings','backend.employee.reports','api.employee.bookings','api.employee.reports',
            'logout','backend.realtime.config','broadcasting.auth','api.realtime.auth',
            'notification.counts','notification.list'];
        if ($request->isMethod('GET') && in_array($name,['backend.bookings.index','backend.bookings.datatable_view'],true)) return redirect()->route('backend.employee.bookings');
        if ($request->isMethod('GET') && $request->is('/','admin','app','app/dashboard')) return redirect()->route('backend.employee.bookings');
        if (in_array($name,$allowed,true)) return $next($request);
        $apiAllowed=['api/logout','api/notification','api/notification-list','api/notifications/realtime-config','api/notifications/read-all','api/webpush/config','api/webpush/subscription'];
        if (in_array($request->path(),$apiAllowed,true)) return $next($request);
        if ($request->isMethod('POST') && preg_match('#^api/notifications/[^/]+/read$#',$request->path())) return $next($request);
        abort(403,__('employee_portal.denied'));
    }
}
