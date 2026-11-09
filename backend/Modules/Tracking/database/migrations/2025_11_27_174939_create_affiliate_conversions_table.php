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
        Schema::create('affiliate_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('affiliates')->onDelete('cascade');
            $table->foreignId('visitor_id')->nullable()->constrained('affiliate_visitors')->nullOnDelete();

            $table->bigInteger('order_id')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('commission', 12, 2)->default(0);

            $table->enum('status', ['pending','approved','rejected','paid'])->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate_conversions');
    }
};
