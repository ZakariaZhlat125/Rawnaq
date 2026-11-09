<?php

namespace Tests\Feature;

use App\Services\Wallet\InsufficientWalletBalance;
use App\Services\Wallet\PointsToWalletConverter;
use App\Services\Wallet\WalletLedger;
use App\Services\Wallet\WalletWithdrawalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Wallet\Models\WithdrawMoney;
use Tests\Feature\Concerns\WalletSchema;
use Tests\TestCase;

class WalletLedgerTest extends TestCase
{
    use WalletSchema;

    private WalletLedger $ledger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createWalletSchema();
        $this->ledger = app(WalletLedger::class);
    }

    public function test_bonus_is_spent_first_and_every_row_records_the_balance_after(): void
    {
        $this->ledger->credit(1, 100, 'topup');
        $this->ledger->credit(1, 50, 'campaign', ['bucket' => 'bonus', 'expires_at' => now()->addDays(10)]);
        $debit = $this->ledger->debit(1, 120, 'payment');

        $this->assertSame(['total' => 30.0, 'cash' => 30.0, 'bonus' => 0.0], $this->balances(1));
        $this->assertSame(50.0, $debit->bonus_amount);
        $this->assertSame(70.0, $debit->cash_amount);
        $this->assertSame(30.0, $debit->balance_after);
        $this->assertSame(0.0, (float) DB::table('wallet_histories')->where('source', 'campaign')->value('bonus_remaining'));
        $this->assertLedgerMatchesBalance(1);
    }

    public function test_overdraw_is_refused_without_side_effects(): void
    {
        $this->ledger->credit(1, 40, 'topup');
        $rows = DB::table('wallet_histories')->count();

        try {
            $this->ledger->debit(1, 40.01, 'payment');
            $this->fail('Overdraw must be refused.');
        } catch (InsufficientWalletBalance $e) {
            $this->assertSame(40.0, $e->available);
        }

        $this->assertSame(40.0, $this->balances(1)['total']);
        $this->assertSame($rows, DB::table('wallet_histories')->count());
        $this->assertLedgerMatchesBalance(1);
    }

    public function test_sequential_debits_racing_for_the_same_balance_never_go_negative(): void
    {
        $this->ledger->credit(1, 100, 'topup');
        $ok = 0;
        foreach (range(1, 5) as $i) {
            try {
                $this->ledger->debit(1, 30, 'payment');
                $ok++;
            } catch (InsufficientWalletBalance) {
            }
        }

        $this->assertSame(3, $ok);
        $this->assertSame(10.0, $this->balances(1)['total']);
        $this->assertLedgerMatchesBalance(1);
    }

    public function test_idempotency_key_applies_a_movement_once(): void
    {
        $first = $this->ledger->credit(1, 25, 'refund', ['idempotency_key' => 'refund:booking:9:cash']);
        $second = $this->ledger->credit(1, 25, 'refund', ['idempotency_key' => 'refund:booking:9:cash']);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(25.0, $this->balances(1)['total']);
    }

    public function test_expired_bonus_is_not_spendable_and_is_removed_by_the_expiry_job(): void
    {
        $this->ledger->credit(1, 20, 'topup');
        $this->ledger->credit(1, 30, 'reward', ['bucket' => 'bonus', 'expires_at' => now()->addDay()]);
        $this->travel(2)->days();

        $this->assertSame(20.0, $this->ledger->spendable(1));
        $this->assertSame(1, $this->ledger->expireDueBonuses());
        $this->assertSame(['total' => 20.0, 'cash' => 20.0, 'bonus' => 0.0], $this->balances(1));
        $this->assertSame(1, DB::table('wallet_histories')->where('source', 'expiry')->count());
        $this->assertSame(0, $this->ledger->expireDueBonuses());
        $this->assertLedgerMatchesBalance(1);
    }

    public function test_disabled_wallet_cannot_spend(): void
    {
        $this->ledger->credit(1, 50, 'topup');
        DB::table('wallet')->where('user_id', 1)->update(['status' => 0]);

        $this->expectException(InsufficientWalletBalance::class);
        $this->ledger->debit(1, 10, 'payment');
    }

    public function test_bonus_balance_cannot_be_withdrawn_and_rejection_releases_the_hold(): void
    {
        $this->ledger->credit(1, 60, 'topup');
        $this->ledger->credit(1, 500, 'campaign', ['bucket' => 'bonus']);
        $withdrawals = app(WalletWithdrawalService::class);
        $bank = ['iban' => 'SA0380000000608010167519', 'account_name' => 'Sara A'];

        try {
            $withdrawals->request(1, 100, $bank);
            $this->fail('Bonus balance must not be withdrawable.');
        } catch (ValidationException) {
        }
        $this->assertSame(0, WithdrawMoney::count());

        $request = $withdrawals->request(1, 60, $bank);
        $this->assertSame('pending', $request->status);
        $this->assertSame(['total' => 500.0, 'cash' => 0.0, 'bonus' => 500.0], $this->balances(1));

        $withdrawals->reject($request, 99, 'IBAN name mismatch');
        $this->assertSame('rejected', $request->fresh()->status);
        $this->assertSame(60.0, $this->balances(1)['cash']);
        $this->assertSame(1, DB::table('notifications')->where('notifiable_id', 1)->where('data', 'like', '%wallet.withdrawal.rejected%')->count());

        $this->expectException(ValidationException::class);
        $withdrawals->markPaid($request->fresh(), 99, 'TRX-1');
    }

    public function test_withdrawal_request_below_minimum_or_for_another_users_bank_is_refused(): void
    {
        $this->ledger->credit(1, 500, 'topup');
        DB::table('banks')->insert(['id' => 7, 'user_id' => 2, 'bank_name' => 'X', 'account_no' => '1']);
        $withdrawals = app(WalletWithdrawalService::class);

        foreach ([[10, ['iban' => 'SA0380000000608010167519', 'account_name' => 'S']], [100, ['bank_id' => 7]]] as [$amount, $bank]) {
            try {
                $withdrawals->request(1, $amount, $bank);
                $this->fail('Request must be refused.');
            } catch (ValidationException) {
            }
        }
        $this->assertSame(500.0, $this->balances(1)['total']);
    }

    public function test_points_convert_to_bonus_balance_in_one_transaction(): void
    {
        DB::table('settings')->insert(['name' => 'point_value', 'val' => '0.5', 'type' => 'string']);
        DB::table('loyalty_points')->insert(['user_id' => 1, 'points' => 500]);
        $converter = app(PointsToWalletConverter::class);

        $result = $converter->convert(1, 300);

        $this->assertSame(150.0, $result['amount']);
        $this->assertSame(200, (int) DB::table('loyalty_points')->where('user_id', 1)->value('points'));
        $this->assertSame(['total' => 150.0, 'cash' => 0.0, 'bonus' => 150.0], $this->balances(1));

        foreach ([50, 400] as $points) { // below the minimum, more than available
            try {
                $converter->convert(1, $points);
                $this->fail('Conversion must be refused.');
            } catch (ValidationException) {
            }
        }
        $this->assertSame(200, (int) DB::table('loyalty_points')->where('user_id', 1)->value('points'));
        $this->assertLedgerMatchesBalance(1);
    }
}
