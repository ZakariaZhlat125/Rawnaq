<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [

        'Modules\Subscriptions\Console\Commands\CheckSubscription',
        \App\Console\Commands\CleanupCartCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
//        $schedule->command('queue:work --tries=3 --stop-when-empty')->withoutOverlapping();

//        $schedule->command('migrate:fresh --seed')->hourlyAt(2);

//        $schedule->command('cart:cleanup')->hourly();
        $schedule->command('basket:clear-expired')->everyMinute();
        $schedule->command('loyalty:expire-points')->daily();
        $schedule->command('wallet:expire-bonus')->hourly()->withoutOverlapping();
        $schedule->command('gifts:expire')->dailyAt('09:00')->withoutOverlapping();
        $schedule->command('customers:send-messages')->timezone('Asia/Riyadh')->dailyAt('08:00')->withoutOverlapping();
        $schedule->command('bookings:send-whatsapp')->everyMinute()->withoutOverlapping();
    }

    /**
     * Get the timezone that should be used by default for scheduled events.
     *
     * @return \DateTimeZone|string|null
     */
    protected function scheduleTimezone()
    {
        return 'Asia/Riyadh';
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
