<?php

namespace Tests\Feature\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;

/** Minimal schema for wallet tests: the real wallet migrations plus the tables they touch. */
trait WalletSchema
{
    protected function createWalletSchema(): void
    {
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        // Customer notifications queue browser-push jobs; keep them off the database queue.
        Queue::fake();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->boolean('is_banned')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary(); $table->string('type');
            $table->morphs('notifiable'); $table->text('data');
            $table->timestamp('read_at')->nullable(); $table->timestamps();
        });
        Schema::create('loyalty_points', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('user_id'); $table->integer('points')->default(0); $table->timestamps();
        });
        Schema::create('invoices', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('user_id');
            $table->json('cart_ids')->nullable(); $table->json('gift_ids')->nullable(); $table->json('product_ids')->nullable();
            $table->decimal('discount_amount', 10, 2)->default(0); $table->decimal('final_total', 10, 2);
            $table->timestamps();
        });
        Schema::create('bookings', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('user_id'); $table->string('status');
            $table->dateTime('start_date_time')->nullable(); $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes(); $table->timestamps();
        });
        Schema::create('booking_transactions', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('booking_id'); $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('external_transaction_id')->nullable(); $table->string('transaction_type')->nullable();
            $table->integer('payment_status')->default(0); $table->timestamps();
        });
        Schema::create('booking_services', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('booking_id'); $table->unsignedBigInteger('service_id')->default(1);
            $table->double('service_price')->default(0); $table->softDeletes(); $table->timestamps();
        });
        Schema::create('booking_packages', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('booking_id'); $table->double('package_price')->default(0);
            $table->softDeletes(); $table->timestamps();
        });
        Schema::create('order_groups', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('user_id')->nullable(); $table->double('grand_total_amount')->default(0); $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('user_id'); $table->unsignedBigInteger('order_group_id')->nullable();
            $table->string('delivery_status')->default('order_placed'); $table->string('payment_status')->default('unpaid'); $table->timestamps();
        });
        Schema::create('banks', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('user_id'); $table->string('bank_name')->nullable(); $table->string('account_no')->nullable(); $table->softDeletes(); $table->timestamps();
        });
        Schema::create('affiliates', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('user_id'); $table->string('status')->default('active');
            $table->decimal('wallet_total', 12, 2)->default(0); $table->decimal('wallet_available', 12, 2)->default(0); $table->timestamps();
        });
        Schema::create('affiliate_conversions', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('affiliate_id'); $table->unsignedBigInteger('referred_user_id')->nullable();
            $table->bigInteger('order_id')->nullable(); $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('commission', 12, 2)->default(0); $table->string('status')->default('pending'); $table->timestamps();
        });
        // Same shape as withdraw_money after 2026_10_17_000004 on MySQL (bank_id nullable).
        Schema::create('withdraw_money', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('user_id'); $table->unsignedBigInteger('bank_id')->nullable();
            $table->double('amount')->nullable(); $table->timestamp('datetime')->nullable(); $table->string('payment_type')->nullable();
            $table->text('transaction')->nullable(); $table->integer('withdraw_money_id')->nullable(); $table->string('status')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id(); $table->string('name')->nullable(); $table->text('val')->nullable();
            $table->string('type', 90)->default('string'); $table->softDeletes(); $table->timestamps();
        });
        foreach ([
            'migrations/2026_02_05_164200_create_loyalty_points_transactions_table.php',
            'migrations/2026_03_03_000000_create_payment_attempts_table.php',
        ] as $migration) {
            (require database_path($migration))->up();
        }
        foreach ([
            '2025_01_15_074136_create_wallet_table.php',
            '2025_01_15_111847_create_wallet_histories_table.php',
        ] as $migration) {
            (require base_path('Modules/Wallet/database/migrations/'.$migration))->up();
        }
        foreach ([
            '2026_10_17_000001_add_ledger_fields_to_wallet_histories.php',
            '2026_10_17_000002_split_wallet_balances.php',
            '2026_10_17_000003_create_wallet_topup_offers_table.php',
            '2026_10_17_000004_add_review_fields_to_withdraw_money.php',
            '2026_10_17_000005_add_purpose_to_payment_attempts.php',
            '2026_10_24_000001_create_payment_gateways_tables.php',
            '2026_10_24_000002_seed_payment_gateways.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        $this->configureGateway('card', ['secret_key' => 'sk_test_wallet']);

        DB::table('users')->insert([
            ['id' => 1, 'first_name' => 'Sara', 'last_name' => 'A', 'mobile' => '0500000001'],
            ['id' => 2, 'first_name' => 'Huda', 'last_name' => 'B', 'mobile' => '0500000002'],
        ]);
    }

    /** Fills a seeded gateway's fields, as an admin would in /app/payment-gateways, and enables it. */
    protected function configureGateway(string $code, array $values): void
    {
        $gateway = \App\Models\PaymentGateway::with('fields')->where('code', $code)->firstOrFail();
        foreach ($gateway->fields as $field) {
            if (array_key_exists($field->key, $values)) {
                $field->update(['value' => $values[$field->key]]);
            }
        }
        $gateway->update(['is_active' => true]);
    }

    protected function balances(int $userId): array
    {
        $wallet = DB::table('wallet')->where('user_id', $userId)->first();

        return [
            'total' => round((float) ($wallet->amount ?? 0), 2),
            'cash' => round((float) ($wallet->cash_balance ?? 0), 2),
            'bonus' => round((float) ($wallet->bonus_balance ?? 0), 2),
        ];
    }

    /** Ledger invariant: credits - debits == stored balance, per bucket. */
    protected function assertLedgerMatchesBalance(int $userId): void
    {
        $rows = DB::table('wallet_histories')->where('user_id', $userId)->whereNotNull('direction')->get();
        $sum = fn (string $col) => round($rows->sum(fn ($r) => ($r->direction === 'credit' ? 1 : -1) * (float) $r->{$col}), 2);
        $b = $this->balances($userId);

        $this->assertSame($b['cash'], $sum('cash_amount'), 'cash ledger');
        $this->assertSame($b['bonus'], $sum('bonus_amount'), 'bonus ledger');
        $this->assertSame($b['total'], round($b['cash'] + $b['bonus'], 2), 'total = cash + bonus');
        $last = $rows->sortBy('id')->last();
        if ($last) {
            $this->assertSame($b['total'], round((float) $last->balance_after, 2), 'balance_after of last row');
        }
    }
}
