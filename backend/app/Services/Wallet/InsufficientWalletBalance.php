<?php

namespace App\Services\Wallet;

class InsufficientWalletBalance extends \RuntimeException
{
    public function __construct(public readonly float $requested, public readonly float $available)
    {
        parent::__construct(__('wallet_ledger.insufficient_balance'));
    }
}
