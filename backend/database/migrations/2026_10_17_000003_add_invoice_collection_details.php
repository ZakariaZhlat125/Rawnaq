<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoices', fn (Blueprint $table) => $table->json('collection_details')->nullable());
    }
    public function down(): void
    {
        Schema::table('invoices', fn (Blueprint $table) => $table->dropColumn('collection_details'));
    }
};
