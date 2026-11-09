<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code', 20)->nullable()->unique();
            $table->unsignedBigInteger('referred_by_user_id')->nullable()->index();
            $table->timestamp('referral_rewarded_at')->nullable();
        });
        DB::table('users')->select('id')->orderBy('id')->chunkById(500, function ($users) {
            foreach ($users as $user) {
                do { $code = 'SC'.strtoupper(bin2hex(random_bytes(6))); }
                while (DB::table('users')->where('referral_code', $code)->exists());
                DB::table('users')->where('id', $user->id)->whereNull('referral_code')->update(['referral_code'=>$code]);
            }
        });
    }
    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['referral_code']);
            $table->dropIndex(['referred_by_user_id']);
            $table->dropColumn(['referral_code','referred_by_user_id','referral_rewarded_at']);
        });
    }
};
