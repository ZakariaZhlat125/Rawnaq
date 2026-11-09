<?php

use App\Http\Controllers\Backend\WalletAdminController;
use App\Http\Controllers\Backend\WalletTopupOffersController;
use App\Http\Controllers\Backend\WalletWithdrawalsController;
use Illuminate\Support\Facades\Route;
use Modules\Wallet\Http\Controllers\WalletController;

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

Route::group(['prefix' => 'app', 'middleware' => 'auth'], function () {
    Route::get('wallet', [WalletAdminController::class, 'index'])->name('wallet.index');
    Route::get('wallet/policy', [WalletAdminController::class, 'policy'])->name('wallet.policy');
    Route::post('wallet/policy', [WalletAdminController::class, 'savePolicy'])->name('wallet.policy.save');
    Route::post('wallet/{user}/adjust', [WalletAdminController::class, 'adjust'])->whereNumber('user')->name('wallet.adjust');

    Route::get('wallet/withdrawals', [WalletWithdrawalsController::class, 'index'])->name('wallet.withdrawals');
    Route::post('wallet/withdrawals/{withdrawal}/approve', [WalletWithdrawalsController::class, 'approve'])->name('wallet.withdrawals.approve');
    Route::post('wallet/withdrawals/{withdrawal}/reject', [WalletWithdrawalsController::class, 'reject'])->name('wallet.withdrawals.reject');
    Route::post('wallet/withdrawals/{withdrawal}/paid', [WalletWithdrawalsController::class, 'markPaid'])->name('wallet.withdrawals.paid');

    Route::get('wallet/topup-offers', [WalletTopupOffersController::class, 'index'])->name('wallet.offers');
    Route::post('wallet/topup-offers', [WalletTopupOffersController::class, 'store'])->name('wallet.offers.store');
    Route::put('wallet/topup-offers/{offer}', [WalletTopupOffersController::class, 'update'])->name('wallet.offers.update');
    Route::delete('wallet/topup-offers/{offer}', [WalletTopupOffersController::class, 'destroy'])->name('wallet.offers.destroy');

    Route::get('wallet-history/{id}', [WalletController::class, 'walletHistory'])->name('wallet.history');
    Route::get('wallet-history-data', [WalletController::class, 'walletHistoryData'])->name('wallet.history-data');
});

Route::get('add/balance-to-wallet', [WalletController::class, 'addbalance'])->middleware('auth')->name('wallet.addbalance');
Route::get('/add/balance/callback', [WalletController::class, 'callback'])->middleware('auth')->name('addbalance.callback');
