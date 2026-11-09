<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gift_cards')) {
            return;
        }

        Schema::table('gift_cards', function (Blueprint $table) {
            if (! Schema::hasColumn('gift_cards', 'claim_token')) {
                $table->string('claim_token', 80)->nullable()->unique()->after('payment_status');
            }

            if (! Schema::hasColumn('gift_cards', 'gift_status')) {
                $table->string('gift_status', 40)->default('pending_payment')->after('claim_token');
            }

            if (! Schema::hasColumn('gift_cards', 'send_channel')) {
                $table->string('send_channel', 20)->nullable()->after('gift_status');
            }

            if (! Schema::hasColumn('gift_cards', 'send_status')) {
                $table->string('send_status', 40)->nullable()->after('send_channel');
            }

            if (! Schema::hasColumn('gift_cards', 'sent_at')) {
                $table->timestamp('sent_at')->nullable()->after('send_status');
            }

            if (! Schema::hasColumn('gift_cards', 'claimed_at')) {
                $table->timestamp('claimed_at')->nullable()->after('sent_at');
            }

            if (! Schema::hasColumn('gift_cards', 'redeemed_at')) {
                $table->timestamp('redeemed_at')->nullable()->after('claimed_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('gift_cards')) {
            return;
        }

        $columns = array_values(array_filter([
            'redeemed_at',
            'claimed_at',
            'sent_at',
            'send_status',
            'send_channel',
            'gift_status',
            'claim_token',
        ], fn (string $column) => Schema::hasColumn('gift_cards', $column)));

        if ($columns !== []) {
            Schema::table('gift_cards', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
