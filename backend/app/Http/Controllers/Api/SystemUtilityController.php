<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class SystemUtilityController extends Controller
{
    public function switchLocale(string $locale)
    {
        if (in_array($locale, ['en', 'ar'], true)) {
            session(['locale' => $locale]);
        }

        return redirect()->back();
    }

    public function clearConfig()
    {
        Artisan::call('config:clear');

        return 'Config cache cleared!';
    }

    public function clearCache()
    {
        Artisan::call('cache:clear');

        return 'Application cache cleared!';
    }

    public function clearRoute()
    {
        Artisan::call('route:clear');

        return 'Route cache cleared!';
    }

    public function modulesList()
    {
        Artisan::call('module:list');

        return nl2br(Artisan::output());
    }

    public function clearView()
    {
        Artisan::call('view:clear');

        return 'View cache cleared!';
    }

    public function clearAll()
    {
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        return 'All caches cleared!';
    }

    public function storageLink()
    {
        return Artisan::call('storage:link');
    }

    public function adminRedirect(Request $request)
    {
        if ($request->user()?->hasRole('employee')) {
            return redirect(RouteServiceProvider::EMPLOYEE_LOGIN_REDIRECT);
        }

        return redirect(RouteServiceProvider::HOME);
    }

    public function currentUser(Request $request)
    {
        return $request->user();
    }

    public function testUpload()
    {
        Storage::disk('public')->put('test.txt', 'hello from laravel');

        return Storage::disk('public')->path('test.txt');
    }
}
