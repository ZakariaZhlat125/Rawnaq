<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('center_infos', function (Blueprint $table) {
            $table->id();
            $table->string('title')->default('مركز عناية سامي');
            $table->text('about')->nullable();
            $table->string('support_phone')->nullable();
            $table->string('customer_service_phone')->nullable();
            $table->string('whatsapp_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('x_url')->nullable();
            $table->string('tiktok_url')->nullable();
            $table->text('address')->nullable();
            $table->string('map_url')->nullable();
            $table->text('services_info')->nullable();
            $table->text('extra_info')->nullable();
            $table->string('cover_image')->nullable();
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('center_infos');
    }
};
