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
        Schema::create('membership_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_ar');
            $table->integer('level')->unique(); // e.g. 1 (Member), 2 (Silver), 3 (Gold), 4 (Platinum)
            $table->integer('min_points')->default(0); // Minimum points required for this tier
            $table->decimal('discount_percentage', 5, 2)->default(0); // Optional global discount for tier
            $table->json('benefits')->nullable(); // JSON to store features/benefits
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membership_tiers');
    }
};
