<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('center_infos', function (Blueprint $table) {
            $table->string('profile_image')->nullable()->after('cover_image');
        });
    }

    public function down(): void
    {
        Schema::table('center_infos', function (Blueprint $table) {
            $table->dropColumn('profile_image');
        });
    }
};
