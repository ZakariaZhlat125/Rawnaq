<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cafe_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cafe_order_id')->constrained('cafe_orders')->cascadeOnDelete();
            $table->foreignId('cafe_item_id')->nullable()->constrained('cafe_items')->nullOnDelete();
            $table->string('item_name');
            $table->decimal('item_price', 10, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('subtotal', 10, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cafe_order_items');
    }
};
