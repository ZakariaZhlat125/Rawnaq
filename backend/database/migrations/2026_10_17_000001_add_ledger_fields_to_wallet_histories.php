<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Turns wallet_histories into a ledger: every movement records its source,
 * how it split between the cash and bonus buckets, and the balances after it.
 * Legacy rows keep their activity_* columns and simply have null ledger fields.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_histories', function (Blueprint $table) {
            $table->string('direction', 10)->nullable()->after('activity_type');      // credit | debit
            $table->string('source', 30)->nullable()->after('direction');             // topup, refund, reward, ...
            $table->string('source_type', 30)->nullable()->after('source');           // booking, order, invoice, payment_attempt, ...
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            $table->decimal('amount', 12, 2)->nullable()->after('source_id');
            $table->decimal('cash_amount', 12, 2)->default(0)->after('amount');
            $table->decimal('bonus_amount', 12, 2)->default(0)->after('cash_amount');
            $table->decimal('balance_after', 12, 2)->nullable()->after('bonus_amount');
            $table->decimal('cash_balance_after', 12, 2)->nullable()->after('balance_after');
            $table->decimal('bonus_balance_after', 12, 2)->nullable()->after('cash_balance_after');
            // Bonus credits are "lots": spending consumes the earliest-expiring lot first,
            // and whatever is left in a lot when it expires is removed by wallet:expire-bonus.
            $table->decimal('bonus_remaining', 12, 2)->nullable()->after('bonus_balance_after');
            $table->timestamp('expires_at')->nullable()->after('bonus_remaining');
            $table->string('idempotency_key', 120)->nullable()->unique()->after('expires_at');
            $table->unsignedBigInteger('created_by')->nullable()->after('idempotency_key');

            $table->index(['user_id', 'source']);
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::table('wallet_histories', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'source']);
            $table->dropIndex(['source_type', 'source_id']);
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn([
                'direction', 'source', 'source_type', 'source_id', 'amount', 'cash_amount', 'bonus_amount',
                'balance_after', 'cash_balance_after', 'bonus_balance_after', 'bonus_remaining', 'expires_at',
                'idempotency_key', 'created_by',
            ]);
        });
    }
};
