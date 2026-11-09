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

        $addSenderName = ! Schema::hasColumn('gift_cards', 'sender_name');
        $addDesign = ! Schema::hasColumn('gift_cards', 'design');

        if ($addSenderName || $addDesign) {
            Schema::table('gift_cards', function (Blueprint $table) use ($addSenderName, $addDesign) {
                if ($addSenderName) {
                    $table->string('sender_name')->nullable()->after('recipient_phone');
                }

                if ($addDesign) {
                    $table->string('design', 50)->default('lux-dark')->after('sender_name');
                }
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('gift_cards')) {
            return;
        }

        $columns = array_values(array_filter([
            Schema::hasColumn('gift_cards', 'design') ? 'design' : null,
            Schema::hasColumn('gift_cards', 'sender_name') ? 'sender_name' : null,
        ]));

        if ($columns !== []) {
            Schema::table('gift_cards', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }
};