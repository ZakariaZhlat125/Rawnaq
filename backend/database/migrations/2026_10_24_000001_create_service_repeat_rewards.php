<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('service_repeat_settings', function(Blueprint $t) { $t->unsignedInteger('id')->primary(); $t->boolean('enabled')->default(false); $t->timestamp('starts_at')->nullable(); });
        \Illuminate\Support\Facades\DB::table('service_repeat_settings')->insert(['id'=>1,'enabled'=>false]);
        Schema::create('service_repeat_rules', function(Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('service_id')->unique(); $t->boolean('enabled')->default(false);
            $t->unsignedInteger('required_visits')->default(5); $t->timestamp('starts_at')->nullable(); $t->timestamps();
        });
        Schema::create('service_repeat_rewards', function(Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id')->index(); $t->unsignedBigInteger('service_id');
            $t->unsignedBigInteger('rule_id'); $t->unsignedInteger('required_visits');
            $t->unsignedBigInteger('booking_id')->nullable()->unique(); $t->timestamp('used_at')->nullable(); $t->timestamps();
        });
        Schema::create('service_repeat_visits', function(Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('booking_id'); $t->unsignedBigInteger('service_id');
            $t->unsignedBigInteger('user_id'); $t->unsignedBigInteger('rule_id');
            $t->unsignedBigInteger('reward_id')->nullable(); $t->timestamps();
            $t->unique(['booking_id','service_id']); $t->index(['user_id','rule_id','reward_id']);
        });
        Schema::table('bookings', fn(Blueprint $t) => $t->unsignedBigInteger('service_repeat_reward_id')->nullable());
    }
    public function down(): void {
        Schema::table('bookings', fn(Blueprint $t) => $t->dropColumn('service_repeat_reward_id'));
        Schema::dropIfExists('service_repeat_visits'); Schema::dropIfExists('service_repeat_rewards'); Schema::dropIfExists('service_repeat_rules'); Schema::dropIfExists('service_repeat_settings');
    }
};
