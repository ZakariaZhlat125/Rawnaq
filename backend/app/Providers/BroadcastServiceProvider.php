<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        \Illuminate\Support\Facades\Route::post('/broadcasting/auth', [\App\Http\Controllers\RealtimeNotificationsController::class, 'auth'])->middleware(['web','auth','throttle:120,1'])->name('broadcasting.auth');

        require base_path('routes/channels.php');
    }
}
