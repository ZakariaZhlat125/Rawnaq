<?php

use Illuminate\Support\Facades\Route;
use Modules\Bank\Http\Controllers\api\BankController;
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

Route::group([], function () {
    Route::resource('bank', BankController::class)->names('bank');
});

Route::post('/save-bank',[BankController::class,'store'])->name('save-bank');