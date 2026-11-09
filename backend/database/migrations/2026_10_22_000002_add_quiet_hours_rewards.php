<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('branch_quiet_hours', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id')->unique();
            $table->boolean('enabled')->default(false);
            $table->unsignedInteger('points')->default(0);
            $table->json('hours');
            $table->timestamps();
        });
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedInteger('quiet_hours_points')->default(0);
            $table->timestamp('quiet_hours_credited_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', fn (Blueprint $table) => $table->dropColumn(['quiet_hours_points', 'quiet_hours_credited_at']));
        Schema::dropIfExists('branch_quiet_hours');
    }
};
