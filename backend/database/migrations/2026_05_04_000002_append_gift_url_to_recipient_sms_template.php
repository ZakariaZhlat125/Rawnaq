<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $setting = DB::table('settings')
            ->where('name', 'taqnyat_recipient')
            ->first();

        if (! $setting || str_contains((string) $setting->val, '[[gift_url]]')) {
            return;
        }

        DB::table('settings')
            ->where('id', $setting->id)
            ->update([
                'val' => rtrim((string) $setting->val) . ' رابط الهدية: [[gift_url]]',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        //
    }
};
