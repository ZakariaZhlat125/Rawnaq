<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affiliate_conversions', function (Blueprint $table) {
            if (!Schema::hasColumn('affiliate_conversions', 'referred_user_id')) {
                $table->foreignId('referred_user_id')
                    ->nullable()
                    ->after('affiliate_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('affiliate_conversions', 'commission_type')) {
                $table->enum('commission_type', ['fixed', 'percentage'])->nullable()->after('commission');
            }

            if (!Schema::hasColumn('affiliate_conversions', 'commission_value')) {
                $table->decimal('commission_value', 12, 2)->default(0)->after('commission_type');
            }

            if (!Schema::hasColumn('affiliate_conversions', 'commission_apply_type')) {
                $table->enum('commission_apply_type', ['first_purchase', 'recurring'])
                    ->default('recurring')
                    ->after('commission_value');
            }

            $table->index(['affiliate_id', 'referred_user_id'], 'affiliate_ref_user_idx');
        });
    }

    public function down(): void
    {
        Schema::table('affiliate_conversions', function (Blueprint $table) {
            $table->dropIndex('affiliate_ref_user_idx');

            if (Schema::hasColumn('affiliate_conversions', 'referred_user_id')) {
                $table->dropConstrainedForeignId('referred_user_id');
            }
            if (Schema::hasColumn('affiliate_conversions', 'commission_apply_type')) {
                $table->dropColumn('commission_apply_type');
            }
            if (Schema::hasColumn('affiliate_conversions', 'commission_value')) {
                $table->dropColumn('commission_value');
            }
            if (Schema::hasColumn('affiliate_conversions', 'commission_type')) {
                $table->dropColumn('commission_type');
            }
        });
    }
};
