<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up() {
  Schema::table('booking_reviews', function(Blueprint $t) { $t->unsignedTinyInteger('branch_rating')->nullable(); $t->json('service_ratings')->nullable(); });
  Schema::create('customer_favorites', function(Blueprint $t) { $t->id(); $t->foreignId('user_id')->constrained('users')->cascadeOnDelete(); $t->string('type',20); $t->unsignedBigInteger('item_id'); $t->timestamps(); $t->unique(['user_id','type','item_id']); });
 }
 public function down() { Schema::dropIfExists('customer_favorites'); Schema::table('booking_reviews', function(Blueprint $t) { $t->dropColumn(['branch_rating','service_ratings']); }); }
};
