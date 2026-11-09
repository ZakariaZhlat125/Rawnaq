<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SyncAffiliateToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->session()->has('affiliate_token') && $request->hasHeader('X-Affiliate-Token')) {
            $request->session()->put('affiliate_token', $request->header('X-Affiliate-Token'));
        }
        return $next($request);
    }
}
