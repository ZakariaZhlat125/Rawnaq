<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Withdrawal requests now go pending -> approved -> paid (or rejected) and are
 * reviewed by staff. Customers may give an IBAN instead of a saved bank record.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('withdraw_money', function (Blueprint $table) {
            $table->string('iban', 34)->nullable()->after('bank_id');
            $table->string('account_name')->nullable()->after('iban');
            $table->string('reference')->nullable()->after('transaction');
            $table->text('review_note')->nullable()->after('status');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('review_note');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->index(['status', 'created_at']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE withdraw_money MODIFY bank_id BIGINT UNSIGNED NULL');
        }

        // Earlier rows were paid out immediately; keep them out of the review queue.
        DB::table('withdraw_money')->whereNull('status')->update(['status' => 'paid']);
    }

    public function down(): void
    {
        Schema::table('withdraw_money', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
            $table->dropColumn(['iban', 'account_name', 'reference', 'review_note', 'reviewed_by', 'reviewed_at']);
        });
    }
};
