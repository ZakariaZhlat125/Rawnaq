<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->string('registration_referral_code', 20)->nullable();
            $table->unsignedInteger('referral_owner_points')->nullable();
            $table->unsignedInteger('referral_member_points')->nullable();
        });
    }
    public function down(): void {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['registration_referral_code', 'referral_owner_points', 'referral_member_points']));
    }
};
