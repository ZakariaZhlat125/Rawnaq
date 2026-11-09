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
            if (! Schema::hasColumn('gift_cards', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('user_id');
            }

            if (! Schema::hasColumn('gift_cards', 'booking_ids')) {
                $table->json('booking_ids')->nullable()->after('requested_services');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('gift_cards')) {
            return;
        }

        $columns = array_values(array_filter([
            'booking_ids',
            'branch_id',
        ], fn (string $column) => Schema::hasColumn('gift_cards', $column)));

        if ($columns !== []) {
            Schema::table('gift_cards', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
