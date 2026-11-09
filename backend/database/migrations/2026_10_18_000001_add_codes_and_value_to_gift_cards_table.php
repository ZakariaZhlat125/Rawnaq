<?php

use App\Services\Gifts\GiftCodeGenerator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gift cards get a type (service / package / value), a short redeem code stored only as
 * an HMAC hash plus an encrypted copy, an expiry date, an origin (customer / admin /
 * corporate / campaign) and the timestamps needed for the status timeline.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gift_cards', function (Blueprint $table) {
            $table->string('type', 20)->default('service')->after('user_id');
            $table->decimal('value_amount', 10, 2)->nullable()->after('subtotal');
            $table->decimal('remaining_amount', 10, 2)->nullable()->after('value_amount');
            $table->string('redeem_code_hash', 64)->nullable()->unique()->after('claim_token');
            $table->text('redeem_code_encrypted')->nullable()->after('redeem_code_hash');
            $table->string('redeem_code_last4', 4)->nullable()->after('redeem_code_encrypted');
            $table->string('source', 20)->default('customer')->after('type');
            $table->unsignedBigInteger('batch_id')->nullable()->after('source');
            $table->unsignedBigInteger('recipient_user_id')->nullable()->after('recipient_phone');
            $table->unsignedBigInteger('created_by')->nullable()->after('batch_id');
            $table->timestamp('expires_at')->nullable()->after('redeemed_at');
            $table->timestamp('opened_at')->nullable()->after('sent_at');
            $table->timestamp('cancelled_at')->nullable()->after('expires_at');
            $table->string('cancel_reason')->nullable()->after('cancelled_at');
            $table->timestamp('refunded_at')->nullable()->after('cancel_reason');
            $table->timestamp('expiry_reminded_at')->nullable()->after('refunded_at');

            $table->index('gift_status');
            $table->index('expires_at');
            $table->index('batch_id');
            $table->index('recipient_user_id');
        });

        // Existing gifts: infer the type, and give paid, unused ones a code and the
        // advertised 6-month validity counted from purchase.
        DB::table('gift_cards')->orderBy('id')->chunkById(200, function ($gifts) {
            foreach ($gifts as $gift) {
                $packages = json_decode((string) $gift->requested_packages, true) ?: [];
                $services = json_decode((string) $gift->requested_services, true) ?: [];
                $update = ['type' => $packages && ! $services ? 'package' : 'service'];

                if ((int) $gift->payment_status === 1 && empty($gift->redeemed_at)) {
                    $update += app(GiftCodeGenerator::class)->generateColumns();
                    $update['expires_at'] = \Carbon\Carbon::parse($gift->created_at ?? now())->addDays(180);
                }

                DB::table('gift_cards')->where('id', $gift->id)->update($update);
            }
        });
    }

    public function down(): void
    {
        Schema::table('gift_cards', function (Blueprint $table) {
            $table->dropIndex(['gift_status']);
            $table->dropIndex(['expires_at']);
            $table->dropIndex(['batch_id']);
            $table->dropIndex(['recipient_user_id']);
            $table->dropUnique(['redeem_code_hash']);
            $table->dropColumn([
                'type', 'value_amount', 'remaining_amount', 'redeem_code_hash', 'redeem_code_encrypted',
                'redeem_code_last4', 'source', 'batch_id', 'recipient_user_id', 'created_by', 'expires_at',
                'opened_at', 'cancelled_at', 'cancel_reason', 'refunded_at', 'expiry_reminded_at',
            ]);
        });
    }
};
