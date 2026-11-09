<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')->orderBy('id')->get(['id', 'name','description'])->each(function ($product) {
            $translations = json_decode($product->name, true);

            if (
                ! is_array($translations)
                || ! array_key_exists('ar', $translations)
                || ! array_key_exists('en', $translations)
            ) {
                $translations = [
                    'ar' => $product->name,
                    'en' => $product->name,
                ];
            }

            $translations2 = json_decode($product->description, true);

            if (
                ! is_array($translations2)
                || ! array_key_exists('ar', $translations2)
                || ! array_key_exists('en', $translations2)
            ) {
                $translations2 = [
                    'ar' => $product->description,
                    'en' => $product->description,
                ];
            }

            DB::table('products')
                ->where('id', $product->id)
                ->update([
                    'name' => json_encode(
                        $translations,
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    ),
                    'description' => json_encode(
                        $translations2,
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    ),
                ]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->json('name')->change();
            $table->json('description')->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('name')->change();
            $table->longtext('description')->change();
        });

        DB::table('products')->orderBy('id')->get(['id', 'name','description'])->each(function ($product) {
            $translations = json_decode($product->name, true);
            $translations2 = json_decode($product->description, true);

            DB::table('products')
                ->where('id', $product->id)
                ->update([
                    'name' => is_array($translations)
                        ? ($translations['ar'] ?? $translations['en'] ?? '')
                        : $product->name,
                    'description' => is_array($translations2)
                        ? ($translations2['ar'] ?? $translations2['en'] ?? '')
                        : $product->description,
                ]);
        });
    }
};