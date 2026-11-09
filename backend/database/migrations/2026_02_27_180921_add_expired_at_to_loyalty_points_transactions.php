<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('loyalty_points_transactions', function (Blueprint $table) {
            //
            $table->timestamp('expired_at')->nullable()->default(null)->after('meta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loyalty_points_transactions', function (Blueprint $table) {
            //
            $table->dropColumn('expired_at');
        });
    }
};
