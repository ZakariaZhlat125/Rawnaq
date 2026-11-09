<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_whatsapp_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id')->index();
            $table->string('event', 20);
            $table->string('deduplication_key', 100)->unique();
            $table->dateTime('booking_start')->nullable();
            $table->string('status', 20)->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->dateTime('available_at');
            $table->dateTime('sent_at')->nullable();
            $table->string('provider_message_id')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->index(['status', 'available_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_whatsapp_notifications');
    }
};
