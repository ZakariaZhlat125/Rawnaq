<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\Translator;
use Illuminate\Support\Facades\URL;
use App\Http\View\Composers\CartCountComposer;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->scoped(\App\Services\QuietHoursRewardService::class);
        if ($this->app->environment('local')) {
            if ((bool) config('sami.telescope_enabled') && PHP_VERSION_ID < 80400 && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
                $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
                $this->app->register(TelescopeServiceProvider::class);
            }

            if (class_exists(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class)) {
                $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
            }
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        \Illuminate\Notifications\DatabaseNotification::observe(\App\Observers\RealtimeNotificationObserver::class);
        \Illuminate\Notifications\DatabaseNotification::observe(\App\Observers\BrowserPushObserver::class);
        foreach ([
            \Modules\Booking\Models\Booking::class,
            \Modules\Booking\Models\BookingService::class,
            \Modules\Booking\Models\BookingTransaction::class,
            \Modules\Product\Models\Order::class,
            \Modules\Wallet\Models\WalletHistory::class,
            \App\Models\LoyaltyPointTransaction::class,
        ] as $model) {
            $model::observe(\App\Observers\CustomerActivityObserver::class);
        }

        // Membership: award points when a booking is completed
        \Modules\Booking\Models\Booking::observe(\App\Observers\ServiceRepeatBookingObserver::class);
        \Modules\Booking\Models\Booking::observe(\App\Observers\QuietHoursBookingObserver::class);
        \Modules\Booking\Models\Booking::observe(\App\Observers\MembershipPointsObserver::class);

        // Wallet: refund cancelled paid bookings / orders according to the wallet policy
        \Modules\Booking\Models\Booking::observe(\App\Observers\WalletRefundObserver::class);
        \Modules\Product\Models\Order::observe(\App\Observers\WalletRefundObserver::class);

        Schema::defaultStringLength(191);


        Paginator::useBootstrap();

        Blade::directive('hasPermission', function ($permissions) {
            return "<?php if(Auth::user()->can({$permissions})): ?>";
        });

        Blade::directive('endhasPermission', function () {
            return '<?php endif; ?>';
        });

        $this->app->singleton('translation.loader', function ($app) {
            return new CustomTranslationLoader($app['files'], $app['path.lang']);
        });

        $this->app->singleton('translator', function ($app) {
            $loader = $app['translation.loader'];

            $locale = $app['config']['app.locale'];

            $trans = new Translator($loader, $locale);

            $trans->setFallback($app['config']['app.fallback_locale']);

            return $trans;
        });

        View::composer('*', CartCountComposer::class);
    }
}
