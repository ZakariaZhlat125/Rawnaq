<?php

use Illuminate\Support\Facades\Route;
use Modules\Booking\Http\Controllers\Backend\API\BookingsController;
use Modules\Booking\Http\Controllers\Backend\API\PaymentController;

Route::get('booking-status', [BookingsController::class, 'statusList']);
Route::get('booking-invoice-download', [Modules\Booking\Http\Controllers\Backend\BookingsController::class, 'downloadInvoice'])->name('bookings.downloadinvoice');
Route::group(['middleware' => 'auth:sanctum', 'as' => 'backend.'], function () {
    Route::apiResource('bookings', BookingsController::class)->names('bookings.api');
    Route::post('booking-update', [BookingsController::class, 'update'])->name('api.booking-update');
    Route::get('booking-list', [BookingsController::class, 'bookingList'])->name('api.booking-list');
    Route::get('booking-detail', [BookingsController::class, 'bookingDetail'])->name('api.booking-detail');
    Route::get('search-booking', [BookingsController::class, 'searchBookings'])->name('api.search-booking');
    Route::post('save-booking', [BookingsController::class, 'store'])->name('api.save-booking');
    Route::post('save-payment', [PaymentController::class, 'savePayment'])->name('api.save-payment');
});
