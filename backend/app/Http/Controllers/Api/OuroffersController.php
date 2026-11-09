<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ouroffersection;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OuroffersController extends Controller
{
    
    public function index()
    {
        $pages = Ouroffersection::where('end_date', '>', Carbon::now())->get();

        return response()->json([
            'status' => true,
            'data' => [
                'pages' => $pages,
            ]
        ]);
    }

}
