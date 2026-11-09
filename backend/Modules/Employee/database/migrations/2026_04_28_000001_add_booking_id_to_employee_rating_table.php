<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_rating', function (Blueprint $table) {
            $table->unsignedBigInteger('booking_id')->nullable()->after('user_id');
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');

            $table->index(['employee_id', 'booking_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_rating', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
            $table->dropColumn('booking_id');
        });
    }
};
