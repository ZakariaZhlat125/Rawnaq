<?php

use Illuminate\Support\Facades\Route;
use Modules\BussinessHour\Http\Controllers\Backend\ShiftController;
use Modules\BussinessHour\Http\Controllers\Backend\BussinessHoursController;

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
/*
*
* Backend Routes
*
* --------------------------------------------------------------------
*/
Route::group(['prefix' => 'app', 'as' => 'backend.', 'middleware' => ['auth']], function () {
    /*
    * These routes need view-backend permission
    * (good if you want to allow more than one group in the backend,
    * then limit the backend features by different roles or permissions)
    *
    * Note: Administrator has all permissions so you do not have to specify the administrator role everywhere.
    */

    /*
     *
     *  Backend BussinessHours Routes
     *
     * ---------------------------------------------------------------------
     */

    Route::group(['prefix' => 'bussinesshours', 'as' => 'bussinesshours.'], function () {
        Route::get('index_list', [BussinessHoursController::class, 'index_list'])->name('index_list');
    });
    Route::resource('bussinesshours', BussinessHoursController::class);

    Route::group(['prefix' => 'shift', 'as' => 'shift.'], function () {
        Route::get('index_list', [ShiftController::class, 'index_list'])->name('index_list');
        Route::get('index', [ShiftController::class, 'index_data'])->name('index_data'); // This is for DataTables
        // Route::get('index', [ShiftController::class, 'index'])->name('index');
        Route::get('edit/{id}', [ShiftController::class, 'edit'])->name('edit');
        Route::post('store', [ShiftController::class, 'store'])->name('store');
        Route::post('update/{id}', [ShiftController::class, 'update'])->name('update');
        Route::delete('delete/{id}', [ShiftController::class, 'destroy'])->name('destroy');
    });

});
