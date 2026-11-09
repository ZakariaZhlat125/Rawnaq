<?php

namespace Tests\Feature;

use App\Models\GiftCard;
use App\Services\Gifts\GiftCardService;
use App\Services\TaqnyatSmsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\GiftSchema;
use Tests\TestCase;

class GiftExpiryTest extends TestCase
{
    use GiftSchema;

    public array $sms = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->createGiftSchema();
        $test = $this;
        $this->app->instance(TaqnyatSmsService::class, new class($test) extends TaqnyatSmsService {
            public function __construct(private $test) {}
            public function sendSms($recipients, $message, $sender = null) { $this->test->sms[] = [$recipients, $message]; return true; }
            public function getLastError(): ?string { return null; }
        });
    }

    public function test_reminder_is_sent_once_before_expiry_then_the_gift_expires(): void
    {
        $gift = $this->paidGift(['expires_at' => now()->addDays(5)]);
        $service = app(GiftCardService::class);

        $this->assertSame(1, $service->remindExpiring());
        $this->assertSame(0, $service->remindExpiring());
        $this->assertCount(1, $this->sms);
        $this->assertStringContainsString($gift->redeemCode(), $this->sms[0][1]);

        $this->travel(6)->days();
        $this->assertSame(1, $service->expireDue());
        $this->assertSame(GiftCard::STATUS_EXPIRED, $gift->fresh()->gift_status);
        $this->assertSame(0.0, $this->balances(1)['total']); // default policy: no refund on expiry
    }

    public function test_extending_an_expired_gift_makes_it_usable_again(): void
    {
        $gift = $this->paidGift(['expires_at' => now()->subDay()]);
        $service = app(GiftCardService::class);
        $service->expireDue();

        $service->extend($gift->fresh(), Carbon::now()->addMonth(), 3, 'goodwill');

        $gift->refresh();
        $this->assertTrue($gift->isUsable());
        $this->assertSame(GiftCard::STATUS_PAID, $gift->gift_status);
        $this->assertSame(['paid', 'expired', 'extended'], $gift->events()->pluck('event')->all());
    }

    public function test_expiry_refund_policy_refunds_unclaimed_gifts(): void
    {
        DB::table('settings')->insert(['name' => 'gift_refund_on_expiry', 'val' => '1', 'type' => 'string']);
        $gift = $this->paidGift(['expires_at' => now()->subDay()]);
        DB::table('invoices')->insert(['user_id' => 1, 'cart_ids' => '[]', 'gift_ids' => json_encode([$gift->id]), 'product_ids' => '[]', 'final_total' => 300]);

        app(GiftCardService::class)->expireDue();

        $this->assertSame(GiftCard::STATUS_REFUNDED, $gift->fresh()->gift_status);
        $this->assertSame(300.0, $this->balances(1)['cash']);
    }
}
