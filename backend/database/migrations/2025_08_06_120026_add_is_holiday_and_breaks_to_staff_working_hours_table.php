<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_working_hours', function (Blueprint $table) {
            $table->tinyInteger('is_holiday')->nullable()->after('end_time');
            $table->longText('breaks')->nullable()->default('[]')->after('is_holiday');
        });
    }

    public function down(): void
    {
        Schema::table('staff_working_hours', function (Blueprint $table) {
            $table->dropColumn('is_holiday');
            $table->dropColumn('breaks');
        });
    }
};
