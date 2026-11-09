<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Package\Models\Package;

class PackagesController extends Controller
{
    public function index()
    {
        $packages = Package::with(['service', 'service.services', 'media'])
            ->where('status', 1)
            ->whereDate('end_date', '>=', now())
            ->take(6)
            ->get();

        return response()->json([
            'status' => true,
            'data' => $packages
        ]);
    }
}
