<?php

namespace Tests\Feature;

use App\Models\GiftCard;
use App\Models\User;
use App\Services\Gifts\GiftCardService;
use App\Services\Gifts\GiftRedemptionService;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\GiftSchema;
use Tests\TestCase;

class GiftBatchTest extends TestCase
{
    use GiftSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createGiftSchema();
    }

    public function test_bulk_issue_creates_unique_live_codes_and_one_is_used_at_a_branch(): void
    {
        $batch = app(GiftCardService::class)->createBatch([
            'name' => 'Corporate X', 'source' => 'corporate', 'type' => 'value', 'quantity' => 100,
            'value_amount' => 150, 'expires_at' => now()->addMonths(3)->toDateString(),
        ], 3);

        $gifts = GiftCard::where('batch_id', $batch->id)->get();
        $this->assertCount(100, $gifts);
        $codes = $gifts->map->redeemCode();
        $this->assertCount(100, $codes->unique());
        $this->assertTrue($gifts->every(fn ($g) => $g->isUsable() && $g->source === 'corporate' && $g->user_id === null));
        $this->assertSame(100, DB::table('gift_card_events')->where('event', 'issued')->count());

        // Admin-issued value card: anyone (including staff's customer) can use it.
        app(GiftRedemptionService::class)->redeemAtBranch($codes->first(), User::find(2), 3);
        $this->assertSame(['total' => 150.0, 'cash' => 0.0, 'bonus' => 150.0], $this->balances(2));
        $this->assertSame('branch', DB::table('gift_card_redemptions')->value('channel'));
    }

    public function test_csv_export_lists_every_code(): void
    {
        $batch = app(GiftCardService::class)->createBatch(['name' => 'Contest', 'source' => 'campaign', 'type' => 'value', 'quantity' => 5, 'value_amount' => 50], 3);

        // Called directly: route permissions are covered by the permission middleware, not this test.
        $response = app(\App\Http\Controllers\Backend\GiftBatchesController::class)->exportCodes($batch);
        ob_start();
        $response->sendContent();
        $csv = ob_get_clean();

        foreach (GiftCard::where('batch_id', $batch->id)->get() as $gift) {
            $this->assertStringContainsString($gift->redeemCode(), $csv);
        }
        $this->assertSame(1, (int) $batch->fresh()->export_count);
    }
}
