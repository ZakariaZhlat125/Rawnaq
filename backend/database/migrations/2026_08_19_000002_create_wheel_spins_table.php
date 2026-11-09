<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wheel_spins', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address')->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('wheel_id')->nullable()->constrained('wheels')->nullOnDelete();
            $table->boolean('won')->default(false);
            $table->string('reward_type')->nullable();
            $table->decimal('reward_value', 10, 2)->nullable();
            $table->boolean('credited')->default(false);
            $table->timestamp('credited_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wheel_spins');
    }
};
