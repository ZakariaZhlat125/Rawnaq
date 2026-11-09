<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Wallet\Http\Controllers\API\WalletController;

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

Route::middleware(['auth:sanctum'])->prefix('v1')->name('api.')->group(function () {
    Route::get('wallet', fn (Request $request) => $request->user())->name('wallet');
});

Route::middleware(['auth:sanctum'])->group(function (){
    Route::post('/save-wallet', [WalletController::class, 'store'])->name('save-wallet');
    Route::get('/user-wallet-balance', [WalletController::class, 'getBalance'])->name('user-wallet-balance');
    Route::post('/wallet-top-up', [WalletController::class, 'walletTopup'])->name('wallet-top-up');
    Route::get('/wallet-history', [WalletController::class, 'walletHistory'])->name('wallet-history');
    Route::post('/withdraw-money', [WalletController::class, 'withdrawMoney'])->name('withdraw-money');
});


