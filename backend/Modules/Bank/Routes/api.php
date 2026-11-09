<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Bank\Http\Controllers\api\BankController;
use Modules\Bank\Http\Controllers\api\WalletController;

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
    Route::get('bank', fn (Request $request) => $request->user())->name('bank');
    
});

Route::middleware(['auth:sanctum'])->group(function (){
    Route::post('/save-bank', [BankController::class, 'store'])->name('api.save-bank');
    Route::get('/user-bank-detail', [BankController::class, 'showBank'])->name('user-bank-detail');
    Route::get('/default-bank', [BankController::class, 'setDefault'])->name('default-bank');
    Route::post('/edit-bank', [BankController::class, 'editBank'])->name('edit-bank');
    Route::delete('delete-bank/{bankId}', [BankController::class, 'deleteBank'])->name('delete-bank');
});

Route::get  ('/hello',function(){
    return ["name"=>"hii"];
})->name('hello');

