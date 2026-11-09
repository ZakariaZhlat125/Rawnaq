<?php

namespace App\Http\Middleware;

use Closure;

class localization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle($request, Closure $next)
    {
        // Check header request and determine localizaton
        $sessionLocal = session()->get('locale') ? session()->get('locale') : 'en';

        // Public browser reads use the CORS-safelisted Accept-Language header.
        // Keep the legacy header working for existing mobile/API clients.
        $local = $request->header('frezka-localization')
            ?: ($request->hasHeader('Accept-Language')
                ? $request->getPreferredLanguage(['ar', 'en'])
                : $sessionLocal);
        // set laravel localization
        app()->setLocale($local);

        // continue request
        return $next($request);
    }
}
