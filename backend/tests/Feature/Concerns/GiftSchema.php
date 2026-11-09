<?php

namespace Tests\Feature\Concerns;

use App\Models\GiftCard;
use App\Services\Gifts\GiftCardService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Wallet schema + gift tables (the real gift migrations on top of the original gift_cards shape). */
trait GiftSchema
{
    use WalletSchema;

    protected function createGiftSchema(): void
    {
        $this->createWalletSchema();

        Schema::create('gift_cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('recipient_name');
            $table->string('recipient_phone')->nullable();
            $table->string('sender_name')->nullable();
            $table->string('design', 50)->default('lux-dark');
            $table->text('message')->nullable();
            $table->json('requested_services')->nullable();
            $table->longText('requested_packages')->nullable();
            $table->json('booking_ids')->nullable();
            $table->string('options_amount')->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->tinyInteger('payment_status')->default(0);
            $table->string('claim_token', 80)->nullable()->unique();
            $table->string('gift_status', 40)->default('pending_payment');
            $table->string('send_channel', 20)->nullable();
            $table->string('send_status', 40)->nullable();
            $table->text('send_error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamps();
        });
        foreach ([
            '2026_10_18_000001_add_codes_and_value_to_gift_cards_table.php',
            '2026_10_18_000002_create_gift_card_events_table.php',
            '2026_10_18_000003_create_gift_card_redemptions_table.php',
            '2026_10_18_000004_create_gift_card_batches_table.php',
            '2026_10_18_000005_create_gift_designs_table.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }

        Schema::create('services', function (Blueprint $table) {
            $table->id(); $table->json('name'); $table->string('slug')->nullable(); $table->double('default_price')->default(0);
            $table->integer('duration_min')->default(30); $table->tinyInteger('status')->default(1); $table->softDeletes(); $table->timestamps();
        });
        Schema::create('packages', function (Blueprint $table) {
            $table->id(); $table->json('name'); $table->json('description')->nullable(); $table->double('package_price')->default(0);
            $table->tinyInteger('status')->default(1); $table->softDeletes(); $table->timestamps();
        });
        Schema::create('service_employees', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('service_id'); $table->unsignedBigInteger('employee_id');
        });
        Schema::create('branch_employee', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('branch_id'); $table->unsignedBigInteger('employee_id');
        });
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable(); $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable(); $table->string('payment_type')->nullable();
        });
        Schema::table('booking_services', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id')->nullable(); $table->dateTime('start_date_time')->nullable();
            $table->integer('duration_min')->default(0); $table->integer('sequance')->default(0); $table->unsignedBigInteger('created_by')->nullable();
        });
        Schema::table('booking_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable();
        });

        DB::table('users')->insert([
            ['id' => 3, 'first_name' => 'Staff', 'last_name' => 'E', 'mobile' => '0500000003'],
            ['id' => 4, 'first_name' => 'Nora', 'last_name' => 'C', 'mobile' => '966500000004'],
        ]);
        DB::table('services')->insert(['id' => 7, 'name' => json_encode(['ar' => 'مساج', 'en' => 'Massage']), 'default_price' => 200]);
        DB::table('service_employees')->insert(['service_id' => 7, 'employee_id' => 3]);
        DB::table('branch_employee')->insert(['branch_id' => 1, 'employee_id' => 3]);
    }

    /** A paid customer gift from user 1, as checkout would leave it. */
    protected function paidGift(array $attributes = []): GiftCard
    {
        $gift = GiftCard::create($attributes + [
            'user_id' => 1,
            'type' => GiftCard::TYPE_VALUE,
            'source' => 'customer',
            'recipient_name' => 'Nora',
            'recipient_phone' => '0500000004',
            'value_amount' => 300,
            'subtotal' => 300,
            'requested_services' => [],
            'requested_packages' => [],
            'payment_status' => 0,
            'gift_status' => GiftCard::STATUS_PENDING_PAYMENT,
            'send_channel' => 'link',
        ]);

        return app(GiftCardService::class)->activate($gift)->fresh();
    }
}
