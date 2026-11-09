<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('packages')->orderBy('id')->get(['id', 'description'])->each(function ($package) {
            $translations = is_string($package->description)
                ? json_decode($package->description, true)
                : null;

            if (
                ! is_array($translations)
                || ! array_key_exists('ar', $translations)
                || ! array_key_exists('en', $translations)
            ) {
                $translations = [
                    'ar' => $package->description,
                    'en' => $package->description,
                ];
            }

            DB::table('packages')
                ->where('id', $package->id)
                ->update([
                    'description' => json_encode(
                        $translations,
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    ),
                ]);
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->json('description')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->longText('description')->nullable()->change();
        });

        DB::table('packages')->orderBy('id')->get(['id', 'description'])->each(function ($package) {
            $translations = is_string($package->description)
                ? json_decode($package->description, true)
                : null;

            DB::table('packages')
                ->where('id', $package->id)
                ->update([
                    'description' => is_array($translations)
                        ? ($translations['ar'] ?? $translations['en'] ?? null)
                        : $package->description,
                ]);
        });
    }
}; 