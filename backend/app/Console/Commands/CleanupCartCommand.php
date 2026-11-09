<?php

namespace App\Console\Commands;

use App\Services\CartExpirationService;
use Illuminate\Console\Command;

class CleanupCartCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cart:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove expired unpaid cart items based on the configured cart duration';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $result = app(CartExpirationService::class)->clearExpired();

        if (($result['duration'] ?? 0) <= 0) {
            $this->info('Cart duration is disabled or set to 0 minutes.');

            return self::SUCCESS;
        }

        $this->info("Expired unpaid cart bookings removed: {$result['bookings']}");
        $this->info("Expired cart products removed: {$result['products']}");
        $this->info("Expired gift cards removed: {$result['gifts']}");
        $this->info("Expired user packages removed: {$result['user_packages']}");

        return self::SUCCESS;
    }
}
