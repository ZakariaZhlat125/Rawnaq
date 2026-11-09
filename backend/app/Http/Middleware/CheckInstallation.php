<?php

namespace App\Http\Middleware;

use App\Helpers\Classes\Helper;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;


class CheckInstallation
{
    /**
     * Handle an incoming request.
     *

     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // storage/installed is written only after a successful install, so once it
            // exists the per-request database and schema checks are skipped.
            if (file_exists(storage_path('installed'))) {

                // Settings are cached (settings.all), unlike a direct query on every request.
                $activeStorage = setting('disc_type') ?: 'local';

                Config::set('filesystems.default', $activeStorage);

                return $next($request);
            } else {

                return redirect()->route('install.index');

            }
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), 'Access denied for user')) {

                return redirect()->route('install.index');
            }

            throw $e;
        }
    }
}
