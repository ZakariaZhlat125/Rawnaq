<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Each time a gift is used: value moved to a wallet, or a service gift applied to a booking. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_card_redemptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('gift_card_id');
            $table->unsignedBigInteger('user_id');                  // who benefited
            $table->string('channel', 20);                           // online | branch
            $table->decimal('amount', 10, 2)->default(0);
            $table->unsignedBigInteger('wallet_history_id')->nullable();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->timestamps();

            $table->index('gift_card_id');
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_card_redemptions');
    }
};
