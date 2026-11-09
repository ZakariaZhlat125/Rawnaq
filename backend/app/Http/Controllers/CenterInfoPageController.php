<?php

namespace App\Http\Controllers;

use App\Models\CenterInfo;

class CenterInfoPageController extends Controller
{
    public function __invoke()
    {
        $centerInfo = CenterInfo::current();
        abort_unless($centerInfo->status, 404);

        return view('frontend.center-info.show', compact('centerInfo'));
    }
}
