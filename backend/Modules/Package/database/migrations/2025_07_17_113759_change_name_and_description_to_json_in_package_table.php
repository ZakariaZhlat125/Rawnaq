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
        DB::table('packages')->get()->each(function ($data) {
            DB::table('packages')
                ->where('id', $data->id)
                ->update([
                    'name' => json_encode(['ar' => $data->name, 'en' => $data->name]),
                    // 'description' => $data->description
                    //     ? json_encode(['ar' => $data->description, 'en' => $data->description])
                    //     : null,
                ]);
        });

        // Step 2: Alter columns to JSON type
        Schema::table('packages', function (Blueprint $table) {
            $table->json('name')->change();
            // $table->json('description')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->text('name')->change();
            // $table->text('description')->nullable()->change();
        });
    }
};
