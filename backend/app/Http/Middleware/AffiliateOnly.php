<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AffiliateOnly
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
         $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->affiliate) {
            abort(403, 'Access denied');
        }

        view()->share('affiliate_layout', true);
        return $next($request);
    }
}
