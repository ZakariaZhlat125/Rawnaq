<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'referred_by_affiliate_id')) {
                $table->unsignedBigInteger('referred_by_affiliate_id')->nullable()->after('remember_token');
                $table->foreign('referred_by_affiliate_id')
                    ->references('id')
                    ->on('affiliates')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'referred_by_affiliate_id')) {
                $table->dropForeign(['referred_by_affiliate_id']);
                $table->dropColumn('referred_by_affiliate_id');
            }
        });
    }
};
