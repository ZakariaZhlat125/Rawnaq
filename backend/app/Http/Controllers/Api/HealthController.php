<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Health check used by the deploy pipeline right after it switches releases.
 * It reports the release id written by deploy/remote.sh so the pipeline can
 * confirm the new code is the one being served, and fails if the database is down.
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::connection()->getPdo();
            $database = true;
        } catch (\Throwable) {
            $database = false;
        }

        $releaseFile = base_path('RELEASE');

        return response()->json([
            'status' => $database ? 'ok' : 'degraded',
            'database' => $database,
            'release' => is_file($releaseFile) ? trim((string) file_get_contents($releaseFile)) : null,
        ], $database ? 200 : 503);
    }
}
