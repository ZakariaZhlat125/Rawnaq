<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gift_cards')) {
            return;
        }

        Schema::table('gift_cards', function (Blueprint $table) {
            if (! Schema::hasColumn('gift_cards', 'send_error')) {
                $table->text('send_error')->nullable()->after('send_status');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('gift_cards') || ! Schema::hasColumn('gift_cards', 'send_error')) {
            return;
        }

        Schema::table('gift_cards', function (Blueprint $table) {
            $table->dropColumn('send_error');
        });
    }
};
