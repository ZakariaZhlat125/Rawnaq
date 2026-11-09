<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired after a wallet movement commits. The Apple/Google Wallet membership pass
 * (section 17) listens to this to push the new balance to the customer's pass.
 */
class WalletBalanceChanged
{
    use Dispatchable;

    public function __construct(
        public readonly int $userId,
        public readonly float $balance,
        public readonly float $cashBalance,
        public readonly float $bonusBalance,
    ) {}
}
