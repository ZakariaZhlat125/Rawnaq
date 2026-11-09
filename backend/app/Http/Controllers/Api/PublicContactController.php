<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;

class PublicContactController extends Controller
{
    public function __invoke()
    {
        return response()->json(['data' => [
            'whatsapp_number' => (string) Setting::get('whatsapp_number', ''),
        ]])->header('Cache-Control', 'no-store');
    }
}
