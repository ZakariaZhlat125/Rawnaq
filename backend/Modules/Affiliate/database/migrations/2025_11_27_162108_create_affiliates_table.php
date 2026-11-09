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
        Schema::create('affiliates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // كود إحالة خاص
            $table->string('ref_code')->unique();

            $table->enum('status', ['pending', 'active', 'banned'])->default('pending');

            // عمولة المسوق الافتراضية
            $table->decimal('commission_rate', 5, 2)->default(3); // 3%

            // محفظة الأرباح
            $table->decimal('wallet_total', 12, 2)->default(0);
            $table->decimal('wallet_available', 12, 2)->default(0);

            // بيانات الدفع
            $table->string('payment_method')->nullable();
            $table->json('payment_data')->nullable();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliates');
    }
};
