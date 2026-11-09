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
        Schema::table('bussinesshours', function (Blueprint $table) {
            $table->unsignedBigInteger('shift_id')->nullable()->after('day');
            $table->foreign('shift_id')->references('id')->on('shifts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bussinesshours', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
            $table->dropColumn('shift_id');
        });
    }
};
