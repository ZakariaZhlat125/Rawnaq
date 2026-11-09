<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LegacyAccessManagement
{
    public function handle(Request $request, Closure $next)
    {
        // The legacy editors bypass the protected-role and membership invariants.
        // Keep bookmarks working, but do not execute legacy mutation endpoints.
        if ($request->isMethod('GET')) {
            return redirect()->route('backend.access.index');
        }
        abort(410, 'استخدم صفحة العضويات والصلاحيات الجديدة لتنفيذ هذا الإجراء.');
    }
}
