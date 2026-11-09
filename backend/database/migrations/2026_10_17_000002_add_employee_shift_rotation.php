<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('branch_employee', function (Blueprint $table) {
            $table->boolean('shift_rotation_enabled')->default(true);
            $table->date('shift_rotation_anchor')->default('2026-02-15');
        });
    }
    public function down(): void
    {
        Schema::table('branch_employee', fn (Blueprint $table) => $table->dropColumn(['shift_rotation_enabled', 'shift_rotation_anchor']));
    }
};
