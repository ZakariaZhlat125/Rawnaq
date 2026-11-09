<?php

use Illuminate\Support\Facades\Route;
use Modules\Affiliate\Http\Controllers\AffiliateController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['auth', 'affiliate.only'])->prefix('affiliate')->name('affiliate.')->group(function () {

        Route::get('/dashboard', [AffiliateController::class, 'dashboard'])->name('dashboard');

        Route::get('/links', [AffiliateController::class, 'links'])->name('links');

        Route::get('/links/create', [AffiliateController::class, 'createLinks'])->name('links/create');

        Route::get('/stats', [AffiliateController::class, 'stats'])->name('stats');

        Route::get('/conversions', [AffiliateController::class, 'conversions'])->name('conversions');

        Route::get('/earnings', [AffiliateController::class, 'earnings'])->name('earnings');

        Route::get('/withdraw', [AffiliateController::class, 'withdraw'])->name('withdraw');

});
