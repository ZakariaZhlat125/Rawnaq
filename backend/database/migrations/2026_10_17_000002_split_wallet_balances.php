<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * cash_balance: money the customer paid in (refundable / withdrawable).
 * bonus_balance: promotional credit (not withdrawable, may expire).
 * wallet.amount stays as the total (cash + bonus) so existing readers keep working;
 * WalletLedger keeps the three columns in sync.
 *
 * Existing balances are treated as cash. Wheel winnings already in wallets cannot be
 * told apart from paid money after the fact, so review them manually if needed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet', function (Blueprint $table) {
            $table->decimal('cash_balance', 12, 2)->default(0)->after('amount');
            $table->decimal('bonus_balance', 12, 2)->default(0)->after('cash_balance');
        });

        DB::table('wallet')->update(['cash_balance' => DB::raw('CASE WHEN amount > 0 THEN amount ELSE 0 END')]);
    }

    public function down(): void
    {
        Schema::table('wallet', function (Blueprint $table) {
            $table->dropColumn(['cash_balance', 'bonus_balance']);
        });
    }
};
