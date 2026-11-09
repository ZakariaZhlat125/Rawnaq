<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    // The column was added by hand on production before this migration ran there.
    public function up(): void {
        if (! Schema::hasColumn('promotions_coupon', 'minimum_booking_amount')) {
            Schema::table('promotions_coupon', fn (Blueprint $t) => $t->decimal('minimum_booking_amount', 12, 2)->default(0));
        }
        //Schema::table('user_coupon_redeem', fn (Blueprint $t) => $t->unsignedBigInteger('invoice_id')->nullable()->unique());
    }
    public function down(): void {
        Schema::table('promotions_coupon', fn (Blueprint $t) => $t->dropColumn('minimum_booking_amount'));
        //Schema::table('user_coupon_redeem', fn (Blueprint $t) => $t->dropColumn('invoice_id'));
    }
};
