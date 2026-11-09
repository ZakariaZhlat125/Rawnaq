<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('cities', function (Blueprint $t) { $t->decimal('home_service_fee', 10, 2)->default(0); });
  Schema::table('bookings', function (Blueprint $t) {
   $t->unsignedBigInteger('home_city_id')->nullable();
   $t->decimal('home_service_fee',10,2)->default(0);
   $t->decimal('home_latitude',10,7)->nullable();
   $t->decimal('home_longitude',10,7)->nullable();
  });
 }
 public function down(): void {
  Schema::table('bookings', fn (Blueprint $t) => $t->dropColumn(['home_city_id','home_service_fee','home_latitude','home_longitude']));
  Schema::table('cities', fn (Blueprint $t) => $t->dropColumn('home_service_fee'));
 }
};
