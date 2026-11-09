<?php

namespace App\Console\Commands;

use App\Services\CartExpirationService;
use Illuminate\Console\Command;

class ClearExpiredBasketItems extends Command
{
    protected $signature = 'basket:clear-expired';
    protected $description = 'Delete expired unpaid basket items based on the configured cart duration';

    public function handle()
    {
        $result = app(CartExpirationService::class)->clearExpired();

        if (($result['duration'] ?? 0) <= 0) {
            $this->info('Cart duration is disabled or set to 0 minutes.');

            return self::SUCCESS;
        }

        $this->info("Cart duration: {$result['duration']} minutes");

        if (! empty($result['expired_before'])) {
            $this->info("Deleting items created before: {$result['expired_before']}");
        }

        $this->info("Expired unpaid cart bookings removed: {$result['bookings']}");
        $this->info("Expired cart products removed: {$result['products']}");
        $this->info("Expired gift cards removed: {$result['gifts']}");
        $this->info("Expired user packages removed: {$result['user_packages']}");

        return self::SUCCESS;
    }
}
