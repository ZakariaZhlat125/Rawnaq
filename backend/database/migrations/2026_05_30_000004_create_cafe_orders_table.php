<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cafe_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cafe_table_id')->constrained('cafe_tables')->cascadeOnDelete();
            $table->string('order_number')->unique();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'preparing', 'ready', 'delivered', 'cancelled'])->default('pending');
            $table->decimal('total', 10, 2)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cafe_orders');
    }
};
