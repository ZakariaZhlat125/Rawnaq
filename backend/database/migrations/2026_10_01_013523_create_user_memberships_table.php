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
        Schema::create('user_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('membership_tier_id')->constrained('membership_tiers')->cascadeOnDelete();
            $table->integer('points_balance')->default(0); // Current available points
            $table->integer('total_points_earned')->default(0); // Lifetime points for tier upgrades
            $table->string('qr_code_token')->unique()->nullable(); // Unique token for scanning
            $table->string('apple_wallet_pass_id')->nullable(); // For Apple Wallet Integration
            $table->string('google_wallet_pass_id')->nullable(); // For Google Wallet Integration
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_memberships');
    }
};
