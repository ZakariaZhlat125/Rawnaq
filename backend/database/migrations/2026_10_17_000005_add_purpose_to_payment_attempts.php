<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_attempts', function (Blueprint $table) {
            $table->string('purpose', 20)->default('checkout')->after('gateway'); // checkout | wallet_topup
            $table->index(['external_id']);
        });
    }

    public function down(): void
    {
        Schema::table('payment_attempts', function (Blueprint $table) {
            $table->dropIndex(['external_id']);
            $table->dropColumn('purpose');
        });
    }
};
