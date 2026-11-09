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
        Schema::table('categories', function (Blueprint $table) {
            $table->float('price_from')->nullable();
            $table->tinyInteger('durMin')->nullable();
            $table->tinyInteger('durMax')->nullable();
            $table->string('image')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('price_from');
            $table->dropColumn('durMin');
            $table->dropColumn('durMax');
            $table->dropColumn('image');
        });
    }
};
