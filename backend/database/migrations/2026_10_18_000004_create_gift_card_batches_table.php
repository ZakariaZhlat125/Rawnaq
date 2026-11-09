<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Bulk issue from the dashboard (corporate orders, competitions, campaigns). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_card_batches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('source', 20)->default('admin');          // admin | corporate | campaign
            $table->string('type', 20);                               // value | service | package
            $table->unsignedInteger('quantity');
            $table->decimal('value_amount', 10, 2)->nullable();
            $table->json('requested_services')->nullable();
            $table->json('requested_packages')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('design', 50)->default('lux-dark');
            $table->timestamp('expires_at')->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedInteger('export_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_card_batches');
    }
};
