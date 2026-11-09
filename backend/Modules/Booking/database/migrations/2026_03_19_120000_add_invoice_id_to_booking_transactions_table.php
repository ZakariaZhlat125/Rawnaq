<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('booking_transactions', 'invoice_id')) {
                $table->unsignedBigInteger('invoice_id')->nullable()->after('booking_id')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('booking_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('booking_transactions', 'invoice_id')) {
                $table->dropIndex(['invoice_id']);
                $table->dropColumn('invoice_id');
            }
        });
    }
};
