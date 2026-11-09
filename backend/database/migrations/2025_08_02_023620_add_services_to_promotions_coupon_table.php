<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('promotions_coupon', function (Blueprint $table) {
            $table->json('services')->nullable()->after('end_date_time');
        });
    }
    
    public function down()
    {
        Schema::table('promotions_coupon', function (Blueprint $table) {
            $table->dropColumn('services');
        });
    }
};
