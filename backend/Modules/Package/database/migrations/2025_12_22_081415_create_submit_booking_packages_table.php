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
        Schema::create('submit_booking_packages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('package_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('user_id');

            $table->date('booking_date');
            $table->string('booking_time');

            $table->text('notes')->nullable();
            $table->double('total_price')->default(0);

            $table->string('status')->default('pending');

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submit_booking_packages');
    }
};
