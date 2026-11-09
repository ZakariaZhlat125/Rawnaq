<?php

namespace App\Console\Commands;

use App\Services\Wallet\WalletLedger;
use Illuminate\Console\Command;

class ExpireWalletBonus extends Command
{
    protected $signature = 'wallet:expire-bonus';

    protected $description = 'Remove promotional wallet balance whose validity has ended';

    public function handle(WalletLedger $ledger): int
    {
        $count = $ledger->expireDueBonuses();
        $this->info("Expired promotional balance in {$count} wallet(s).");

        return self::SUCCESS;
    }
}
