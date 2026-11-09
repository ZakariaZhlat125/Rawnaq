<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_topup_offers', function (Blueprint $table) {
            $table->id();
            $table->string('title_ar');
            $table->string('title_en')->nullable();
            $table->decimal('min_amount', 12, 2);                  // "top up 500 ..."
            $table->decimal('bonus_amount', 12, 2);                // "... get 50"
            $table->unsignedInteger('bonus_expiry_days')->nullable(); // null = bonus never expires
            $table->unsignedInteger('max_uses_per_user')->nullable(); // null = unlimited
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_topup_offers');
    }
};
