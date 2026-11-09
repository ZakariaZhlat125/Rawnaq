<?php

namespace Tests\Feature;

use App\Models\GiftCard;
use App\Models\User;
use App\Services\Gifts\GiftCardService;
use App\Services\Gifts\GiftRedemptionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Modules\Booking\Models\Booking;
use Tests\Feature\Concerns\GiftSchema;
use Tests\TestCase;

class GiftRedeemTest extends TestCase
{
    use GiftSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createGiftSchema();
    }

    public function test_value_card_code_adds_promotional_balance_once_and_is_stored_hashed(): void
    {
        $gift = $this->paidGift();
        $code = $gift->redeemCode();
        $this->assertMatchesRegularExpression('/^SAMI-[2-9A-Z]{4}-[2-9A-Z]{4}$/', $code);
        $this->assertNotSame($code, $gift->redeem_code_hash);
        $this->assertSame(0, DB::table('gift_cards')->where('redeem_code_hash', 'like', '%'.substr($code, 5, 4).'%')->where('redeem_code_encrypted', 'like', '%'.$code.'%')->count());
        $this->assertSame(now()->addDays(180)->toDateString(), $gift->expires_at->toDateString());

        Sanctum::actingAs(User::find(2));
        $this->postJson('/api/gifts/redeem', ['code' => strtolower(str_replace('-', ' ', $code))])->assertOk();
        $this->postJson('/api/gifts/redeem', ['code' => $code])->assertStatus(422);

        $this->assertSame(['total' => 300.0, 'cash' => 0.0, 'bonus' => 300.0], $this->balances(2));
        $lot = DB::table('wallet_histories')->where('source', 'gift')->first();
        $this->assertSame($gift->expires_at->toDateTimeString(), \Carbon\Carbon::parse($lot->expires_at)->toDateTimeString());
        $gift->refresh();
        $this->assertSame(GiftCard::STATUS_REDEEMED, $gift->gift_status);
        $this->assertSame(2, (int) $gift->recipient_user_id);
        $this->assertSame(1, DB::table('gift_card_redemptions')->where('gift_card_id', $gift->id)->count());
        $this->assertSame(['paid', 'claimed', 'redeemed'], $gift->events()->pluck('event')->all());
    }

    public function test_buyer_cannot_redeem_their_own_value_card(): void
    {
        $gift = $this->paidGift();
        Sanctum::actingAs(User::find(1));

        $this->postJson('/api/gifts/redeem', ['code' => $gift->redeemCode()])->assertStatus(422);
        $this->assertSame(0.0, $this->balances(1)['total']);
    }

    public function test_wrong_codes_are_locked_out(): void
    {
        Sanctum::actingAs(User::find(2));
        $statuses = [];
        foreach (range(1, 12) as $i) {
            $statuses[] = $this->postJson('/api/gifts/redeem', ['code' => 'SAMI-2222-'.str_pad((string) $i, 4, '3')])->status();
        }

        $this->assertSame(422, $statuses[0]);
        $this->assertSame(429, end($statuses));
    }

    public function test_expired_and_cancelled_gifts_cannot_be_used(): void
    {
        $expired = $this->paidGift(['expires_at' => now()->subDay()]);
        $cancelled = $this->paidGift();
        app(GiftCardService::class)->cancel($cancelled, null, 'test', false);
        $redeem = app(GiftRedemptionService::class);

        foreach ([$expired, $cancelled] as $gift) {
            try {
                $redeem->redeemCode($gift->redeemCode(), User::find(2));
                $this->fail('Gift must not be usable.');
            } catch (ValidationException) {
            }
        }
        $this->assertSame(0.0, $this->balances(2)['total']);
    }

    public function test_service_gift_is_claimed_booked_online_and_restored_if_the_booking_is_cancelled(): void
    {
        $gift = $this->paidGift(['type' => GiftCard::TYPE_SERVICE, 'value_amount' => null, 'subtotal' => 200, 'requested_services' => [7], 'branch_id' => 1]);
        Sanctum::actingAs(User::find(4)); // mobile 9665... matches the recipient's 05... number

        $received = $this->getJson('/api/gifts/received')->assertOk()->json('data');
        $this->assertSame($gift->id, $received[0]['id']);
        $this->postJson("/api/gifts/received/{$gift->id}/claim")->assertOk();
        $this->assertSame(GiftCard::STATUS_CLAIMED, $gift->fresh()->gift_status);

        $date = now()->addDays(3)->toDateString();
        $this->postJson("/api/gifts/received/{$gift->id}/book", ['appointments' => [['service_id' => 7, 'employee_id' => 99, 'date' => $date, 'time' => '15:00']]])
            ->assertStatus(422); // staff member not at the gift branch
        $bookingIds = $this->postJson("/api/gifts/received/{$gift->id}/book", ['appointments' => [['service_id' => 7, 'employee_id' => 3, 'date' => $date, 'time' => '15:00']]])
            ->assertOk()->json('booking_ids');

        $booking = Booking::find($bookingIds[0]);
        $this->assertSame(4, (int) $booking->user_id);
        $this->assertSame(GiftCard::STATUS_REDEEMED, $gift->fresh()->gift_status);

        $booking->update(['status' => 'cancelled']);
        $this->assertSame(GiftCard::STATUS_CLAIMED, $gift->fresh()->gift_status);
        $this->assertNull($gift->fresh()->redeemed_at);
    }

    public function test_sender_sees_the_timeline_and_opening_by_the_buyer_is_not_counted(): void
    {
        $gift = $this->paidGift(['type' => GiftCard::TYPE_SERVICE, 'value_amount' => null, 'subtotal' => 200, 'requested_services' => [7]]);

        Sanctum::actingAs(User::find(1));
        $this->getJson('/api/gift-cards/claim/'.$gift->claim_token)->assertOk();
        $this->assertNull($gift->fresh()->opened_at);

        $this->app['auth']->forgetGuards();
        $page = $this->getJson('/api/gift-cards/claim/'.$gift->claim_token)->assertOk()->json('data');
        $this->assertSame($gift->redeemCode(), $page['redeem_code']);
        app(GiftRedemptionService::class)->redeemCode($gift->redeemCode(), User::find(2));

        Sanctum::actingAs(User::find(1));
        $timeline = $this->getJson("/api/gifts/sent/{$gift->id}")->assertOk()->json('data.timeline');
        $this->assertSame(['paid', 'opened', 'claimed'], array_column($timeline, 'event'));
        $this->getJson("/api/gifts/sent/{$gift->id}")->assertJsonPath('data.status', 'claimed');

        Sanctum::actingAs(User::find(2));
        $this->getJson("/api/gifts/sent/{$gift->id}")->assertNotFound(); // someone else's purchase
    }

    public function test_claimed_gift_cannot_be_cancelled_and_unclaimed_purchase_is_refunded_to_the_buyer(): void
    {
        $claimed = $this->paidGift(['type' => GiftCard::TYPE_SERVICE, 'value_amount' => null, 'subtotal' => 200, 'requested_services' => [7]]);
        app(GiftRedemptionService::class)->redeemCode($claimed->redeemCode(), User::find(2));
        try {
            app(GiftCardService::class)->cancel($claimed->fresh(), 3, 'customer asked');
            $this->fail('A claimed gift must not be cancellable.');
        } catch (ValidationException) {
        }

        $gift = $this->paidGift();
        DB::table('invoices')->insert(['id' => 50, 'user_id' => 1, 'cart_ids' => '[]', 'gift_ids' => json_encode([$gift->id]), 'product_ids' => '[]', 'final_total' => 300]);
        app(GiftCardService::class)->cancel($gift, 3, 'wrong number');

        $this->assertSame(GiftCard::STATUS_REFUNDED, $gift->fresh()->gift_status);
        $this->assertSame(300.0, $this->balances(1)['cash']);
        $this->assertSame(['paid', 'cancelled', 'refunded'], $gift->events()->pluck('event')->all());

        app(GiftCardService::class)->expireDue(); // a refunded gift is never touched again
        $this->assertSame(300.0, $this->balances(1)['cash']);
    }

    public function test_branch_applies_a_service_gift_to_the_customers_booking(): void
    {
        $gift = $this->paidGift(['type' => GiftCard::TYPE_SERVICE, 'value_amount' => null, 'subtotal' => 200, 'requested_services' => [7]]);
        $booking = Booking::create(['user_id' => 2, 'status' => 'pending', 'start_date_time' => now()->addDay()->format('Y-m-d H:i:s'), 'branch_id' => 1]);
        $redeem = app(GiftRedemptionService::class);

        $redeem->redeemAtBranch($gift->redeemCode(), User::find(2), 3, $booking);

        $this->assertSame('confirmed', $booking->fresh()->status);
        $this->assertSame(1, DB::table('booking_transactions')->where('booking_id', $booking->id)->where('external_transaction_id', 'GIFT-'.$gift->id)->count());
        $this->assertSame(3, (int) DB::table('gift_card_redemptions')->value('staff_id'));
        $this->expectException(ValidationException::class);
        $redeem->redeemAtBranch($gift->redeemCode(), User::find(2), 3, $booking);
    }
}
