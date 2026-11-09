<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->uuid('token')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('gateway', 20);
            $table->string('isBuyNow', 20)->nullable();
            $table->string('currency', 3)->default('SAR');
            $table->decimal('gross_amount', 10, 2)->default(0);
            $table->decimal('amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->string('coupon_code')->nullable();
            $table->json('cart_ids')->nullable();
            $table->json('gift_ids')->nullable();
            $table->json('submethods')->nullable();
            $table->string('status', 20)->default('initiated');
            $table->string('external_id')->nullable();
            $table->text('payment_url')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_attempts');
    }
};
