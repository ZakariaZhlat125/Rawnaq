<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FaqContent;
use Illuminate\Http\JsonResponse;

class FaqController extends Controller
{
    public function __invoke(FaqContent $content): JsonResponse
    {
        return response()->json(['status' => true, 'data' => $content->published()]);
    }
}
