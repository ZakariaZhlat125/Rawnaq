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

        if (Schema::hasColumn('gift_cards', 'requested_services')) {
            Schema::table('gift_cards', function (Blueprint $table) {
                $table->json('requested_services')->change();
            });
        }

        $shouldAddUserId = ! Schema::hasColumn('gift_cards', 'user_id');
        $shouldAddPaymentStatus = ! Schema::hasColumn('gift_cards', 'payment_status');
        $shouldAddMessage = ! Schema::hasColumn('gift_cards', 'message');

        if ($shouldAddUserId || $shouldAddPaymentStatus || $shouldAddMessage) {
            Schema::table('gift_cards', function (Blueprint $table) use ($shouldAddUserId, $shouldAddPaymentStatus, $shouldAddMessage) {
                if ($shouldAddUserId) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('id');
                }

                if ($shouldAddPaymentStatus) {
                    $table->tinyInteger('payment_status')->default(0)->after('subtotal');
                }

                if ($shouldAddMessage) {
                    $table->text('message')->nullable()->after('recipient_phone');
                }
            });
        }

        $columnsToDrop = array_values(array_filter([
            'ref',
            'balance',
            'delivery_method',
            'sender_name',
            'sender_phone',
            'package_ids',
            'coupons',
        ], fn (string $column) => Schema::hasColumn('gift_cards', $column)));

        if ($columnsToDrop !== []) {
            Schema::table('gift_cards', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('gift_cards')) {
            return;
        }

        $shouldAddRef = ! Schema::hasColumn('gift_cards', 'ref');
        $shouldAddBalance = ! Schema::hasColumn('gift_cards', 'balance');
        $shouldAddDeliveryMethod = ! Schema::hasColumn('gift_cards', 'delivery_method');
        $shouldAddSenderName = ! Schema::hasColumn('gift_cards', 'sender_name');
        $shouldAddSenderPhone = ! Schema::hasColumn('gift_cards', 'sender_phone');
        $shouldAddPackageIds = ! Schema::hasColumn('gift_cards', 'package_ids');
        $shouldAddCoupons = ! Schema::hasColumn('gift_cards', 'coupons');

        if (
            $shouldAddRef ||
            $shouldAddBalance ||
            $shouldAddDeliveryMethod ||
            $shouldAddSenderName ||
            $shouldAddSenderPhone ||
            $shouldAddPackageIds ||
            $shouldAddCoupons
        ) {
            Schema::table('gift_cards', function (Blueprint $table) use (
                $shouldAddRef,
                $shouldAddBalance,
                $shouldAddDeliveryMethod,
                $shouldAddSenderName,
                $shouldAddSenderPhone,
                $shouldAddPackageIds,
                $shouldAddCoupons
            ) {
                if ($shouldAddRef) {
                    $table->string('ref')->nullable();
                }

                if ($shouldAddBalance) {
                    $table->decimal('balance', 10, 2)->default(0);
                }

                if ($shouldAddDeliveryMethod) {
                    $table->string('delivery_method')->nullable();
                }

                if ($shouldAddSenderName) {
                    $table->string('sender_name')->nullable();
                }

                if ($shouldAddSenderPhone) {
                    $table->string('sender_phone')->nullable();
                }

                if ($shouldAddPackageIds) {
                    $table->json('package_ids')->nullable();
                }

                if ($shouldAddCoupons) {
                    $table->json('coupons')->nullable();
                }
            });
        }

        $columnsToDrop = array_values(array_filter([
            'user_id',
            'payment_status',
            'message',
        ], fn (string $column) => Schema::hasColumn('gift_cards', $column)));

        if ($columnsToDrop !== []) {
            Schema::table('gift_cards', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }
    }
};
