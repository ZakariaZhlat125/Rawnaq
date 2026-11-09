<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Update existing data to be valid JSON
        DB::table('categories')->get()->each(function ($data) {
            DB::table('categories')
                ->where('id', $data->id)
                ->update([
                    'name' => json_encode(['ar' => $data->name, 'en' => $data->name]),
                ]);
        });

        // Step 2: Alter columns to JSON type
        Schema::table('categories', function (Blueprint $table) {
            $table->json('name')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->text('name')->change();
        });
    }
};
