<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cafe_orders', function (Blueprint $table) {
            $table->string('client_token')->nullable()->unique()->after('order_number');
        });
    }

    public function down(): void
    {
        Schema::table('cafe_orders', function (Blueprint $table) {
            $table->dropColumn('client_token');
        });
    }
};
