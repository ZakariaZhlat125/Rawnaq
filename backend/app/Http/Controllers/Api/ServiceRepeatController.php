<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Services\ServiceRepeatRewards;
use Illuminate\Http\Request;
class ServiceRepeatController extends Controller {
    public function __invoke(Request $request) {
        return response()->json(['data'=>app(ServiceRepeatRewards::class)->summary((int)$request->user()->id)])->header('Cache-Control','private, no-store');
    }
}
