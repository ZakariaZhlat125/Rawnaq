<?php

namespace App\Console\Commands;

use App\Services\Gifts\GiftCardService;
use Illuminate\Console\Command;

class ExpireGiftCards extends Command
{
    protected $signature = 'gifts:expire';

    protected $description = 'Expire gift cards past their validity and remind recipients before expiry';

    public function handle(GiftCardService $gifts): int
    {
        $reminded = $gifts->remindExpiring();
        $expired = $gifts->expireDue();
        $this->info("Reminded {$reminded} recipient(s), expired {$expired} gift(s).");

        return self::SUCCESS;
    }
}
