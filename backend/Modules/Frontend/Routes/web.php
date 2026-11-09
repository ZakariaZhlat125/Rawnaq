<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Legacy Frontend Redirects
|--------------------------------------------------------------------------
|
| The Laravel Blade frontend has been retired. The dashboard remains the only
| web UI in this app; the standalone frontend lives in the sibling front-end
| project and should consume API routes instead.
|
*/

$dashboardRedirect = fn () => redirect('/app');

Route::get('/', $dashboardRedirect)->name('frontend.home');
Route::get('/about', $dashboardRedirect)->name('frontend.about');
Route::get('/services', $dashboardRedirect)->name('frontend.services');
Route::get('/services/category/{id}', $dashboardRedirect)->name('frontend.category.details');
Route::get('/services/{id}', $dashboardRedirect)->name('frontend.service.details');
Route::get('/product/{id}', $dashboardRedirect)->name('frontend.product.details');
Route::get('/contact', $dashboardRedirect)->name('frontend.contact');
Route::get('/branches', $dashboardRedirect)->name('frontend.branches');
Route::get('/Ouroffers', $dashboardRedirect)->name('frontend.Ouroffers');
Route::get('/TermsAndConditions', $dashboardRedirect)->name('frontend.TermsAndConditions');
Route::get('/Packages', $dashboardRedirect)->name('frontend.Packages');
Route::get('/Shop', $dashboardRedirect)->name('frontend.Shop');
Route::get('/payment', $dashboardRedirect)->name('paymentMethods');
Route::get('/become-affiliate', $dashboardRedirect)->name('frontend.become.affiliate');
Route::post('/become-affiliate', $dashboardRedirect)->name('frontend.become.affiliate.submit');
Route::get('/sami-care-card', $dashboardRedirect)->name('frontend.sami-care-card');
Route::get('/sami-care-card/qr', $dashboardRedirect)->name('frontend.sami-care-card.qr');
