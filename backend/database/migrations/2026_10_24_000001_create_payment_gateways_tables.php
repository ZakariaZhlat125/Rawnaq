<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per gateway customers can pay with. `code` is what checkout sends as
        // `gateway` and what payment_attempts.gateway stores; `driver` picks the PHP
        // integration (config/payment_gateways.php), so two accounts can share a driver.
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('driver', 30);
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Credentials and settings of a gateway, one row per input in the dashboard.
        // `value` is encrypted with APP_KEY.
        Schema::create('payment_gateway_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_gateway_id')->constrained('payment_gateways')->cascadeOnDelete();
            $table->string('key', 64);
            $table->string('label');
            $table->string('type', 20)->default('textarea'); // textarea | select
            $table->json('options')->nullable();              // select: {"value": "label"}
            $table->text('value')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_secret')->default(false);     // masked in the dashboard
            $table->string('help_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['payment_gateway_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_fields');
        Schema::dropIfExists('payment_gateways');
    }
};
