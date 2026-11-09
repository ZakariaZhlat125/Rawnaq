<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Frontend\Http\Controllers\FrontendController;

/*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    |
    | Here is where you can register API routes for your application. These
    | routes are loaded by the RouteServiceProvider within a group which
    | is assigned the "api" middleware group. Enjoy building your API!
    |
*/

// Public API routes for frontend
Route::prefix('v1')->name('api.')->group(function () {
    Route::get('services', [FrontendController::class, 'getServices'])->name('services');
    Route::get('services/{id}', [FrontendController::class, 'getServiceDetails'])->name('service.details');
    Route::get('packages', [FrontendController::class, 'getPackages'])->name('packages');
    Route::get('packages/{id}', [FrontendController::class, 'getPackageDetails'])->name('package.details');
});

Route::middleware(['auth:sanctum'])->prefix('v1')->name('api.')->group(function () {
    Route::get('frontend', fn (Request $request) => $request->user())->name('frontend');
});
