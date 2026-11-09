<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customer_message_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('occasion');
            $table->string('birthday_key')->nullable()->unique();
            $table->text('message');
            $table->timestamp('send_at')->index();
            $table->timestamps();
        });
        Schema::create('customer_message_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('customer_message_campaigns')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('pending')->index();
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['campaign_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_message_deliveries');
        Schema::dropIfExists('customer_message_campaigns');
    }
};
