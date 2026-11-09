<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('arrived_at')->nullable();
            $table->unsignedBigInteger('arrival_confirmed_by')->nullable();
            $table->string('arrival_source', 20)->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('bookings', fn (Blueprint $table) => $table->dropColumn(['arrived_at', 'arrival_confirmed_by', 'arrival_source']));
    }
};
