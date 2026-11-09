<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Status timeline of each gift (created, paid, sent, opened, claimed, booked, redeemed, ...). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_card_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('gift_card_id');
            $table->string('event', 30);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['gift_card_id', 'id']);
            $table->index(['event', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_card_events');
    }
};
