<?php

namespace App\Http\View\Composers;

use App\Services\CartExpirationService;
use Modules\Booking\Models\Booking;
use App\Models\Cart;
use App\Models\GiftCard;
use Illuminate\View\View;
use Modules\Package\Models\BookingPackages;

/**
 * File: app/Http/View/Composers/CartCountComposer.php
 *
 * Injects $cartCount into every view automatically.
 * Register it in AppServiceProvider (see below).
 */
class CartCountComposer
{
    public function compose(View $view): void
    {
        if (\App\Services\EmployeeAccess::limited(auth()->user())) {
            $view->with('cartCount',0);
            return;
        }
        $count = 0;

        if (auth()->check()) {
            $userId = auth()->id();
            app(CartExpirationService::class)->clearExpired($userId);

            // Products in cart
            $productCount = Cart::where('user_id', $userId)->count();

            // Active service bookings in cart
            $services = Booking::with('service.service', 'service.employee')
                ->where('created_by', $userId)
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->where('payment_type', 'cart')
                ->unpaid()
                ->whereNull('deleted_by')
                ->get();
            $serviceCount  = $services->filter(fn($b) => $b->service)->count();
           

            // Unpaid gift cards
            $giftCount = GiftCard::where('user_id', $userId)
                ->where('payment_status', 0)
                ->count();

            // Booking packages (same conditions as index())
            $packageCount = BookingPackages::whereHas('booking', function ($q) use ($userId) {
                $q->where('created_by', $userId)
                    ->whereNotIn('status', ['cancelled', 'completed'])
                    ->where('payment_type', 'cart')
                    ->unpaid()
                    ->whereNull('deleted_by');
            })->count();

            $count = $productCount + $serviceCount + $giftCount + $packageCount;
        }

        $view->with('cartCount', $count);
    }
}
