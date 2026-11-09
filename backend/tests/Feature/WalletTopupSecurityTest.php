<?php

namespace Tests\Feature;

use App\Models\PaymentAttempt;
use App\Models\User;
use App\Services\Payment\PaymentOrchestratorService;
use App\Services\Wallet\WalletLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Modules\Wallet\Models\WalletTopupOffer;
use Tests\Feature\Concerns\WalletSchema;
use Tests\TestCase;

class WalletTopupSecurityTest extends TestCase
{
    use WalletSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createWalletSchema();
    }

    private function fakeTap(array $charge): void
    {
        Http::fake(['api.tap.company/*' => Http::response($charge + ['id' => 'chg_1'])]);
    }

    private function attempt(float $amount, array $meta = []): PaymentAttempt
    {
        return PaymentAttempt::create([
            'token' => (string) Str::uuid(), 'user_id' => 1, 'gateway' => 'card',
            'purpose' => PaymentAttempt::PURPOSE_WALLET_TOPUP, 'currency' => 'SAR',
            'gross_amount' => $amount, 'amount' => $amount, 'cart_ids' => [], 'gift_ids' => [], 'submethods' => [],
            'status' => PaymentAttempt::STATUS_REDIRECTED, 'external_id' => 'chg_1', 'meta' => $meta,
        ]);
    }

    private function gatewayCallback(PaymentAttempt $attempt, string $tapId = 'chg_1'): array
    {
        return app(PaymentOrchestratorService::class)->handleCallback('card', $attempt->token, Request::create('/cb', 'GET', ['tap_id' => $tapId]));
    }

    public function test_legacy_top_up_endpoint_no_longer_adds_balance(): void
    {
        Sanctum::actingAs(User::find(1));

        $this->postJson('/api/wallet-top-up', ['amount' => 100000, 'transaction_type' => 'x', 'transaction_id' => 'fake'])
            ->assertStatus(410);

        $this->assertSame(0.0, $this->balances(1)['total']);
    }

    public function test_save_wallet_ignores_a_client_supplied_balance(): void
    {
        Sanctum::actingAs(User::find(1));

        $this->postJson('/api/save-wallet', ['amount' => 100000, 'status' => 1])->assertCreated();

        $this->assertSame(['total' => 0.0, 'cash' => 0.0, 'bonus' => 0.0], $this->balances(1));
    }

    public function test_legacy_withdraw_ignores_user_id_and_only_holds_the_callers_cash(): void
    {
        app(WalletLedger::class)->credit(2, 500, 'topup');
        Sanctum::actingAs(User::find(1));

        $this->postJson('/api/withdraw-money', [
            'user_id' => 2, 'amount' => 100, 'iban' => 'SA0380000000608010167519', 'account_name' => 'Sara',
        ])->assertStatus(422);

        $this->assertSame(500.0, $this->balances(2)['total']);
        $this->assertSame(0, DB::table('withdraw_money')->count());
    }

    public function test_top_up_creates_a_payment_and_adds_nothing_until_the_gateway_confirms(): void
    {
        Http::fake(['api.tap.company/*' => Http::response(['id' => 'chg_new', 'transaction' => ['url' => 'https://pay.example/chg_new']])]);
        Sanctum::actingAs(User::find(1));

        $this->postJson('/api/wallet/topup', ['amount' => 100])
            ->assertOk()
            ->assertJson(['status' => 'redirect', 'payment_url' => 'https://pay.example/chg_new']);

        $attempt = PaymentAttempt::firstOrFail();
        $this->assertSame(PaymentAttempt::PURPOSE_WALLET_TOPUP, $attempt->purpose);
        $this->assertSame('chg_new', $attempt->external_id);
        $this->assertSame(0.0, $this->balances(1)['total']);

        $this->postJson('/api/wallet/topup', ['amount' => 1])->assertStatus(422); // below wallet_topup_min
    }

    public function test_captured_charge_with_matching_amount_is_credited_exactly_once(): void
    {
        $this->fakeTap(['status' => 'CAPTURED', 'amount' => 100, 'currency' => 'SAR']);
        $attempt = $this->attempt(100);

        $this->assertSame('paid', $this->gatewayCallback($attempt)['status']);
        $this->assertSame('paid', $this->gatewayCallback($attempt->fresh())['status']);
        app(\App\Services\Wallet\WalletTopupService::class)->creditPaidAttempt($attempt->fresh());

        $this->assertSame(['total' => 100.0, 'cash' => 100.0, 'bonus' => 0.0], $this->balances(1));
        $this->assertSame(PaymentAttempt::STATUS_PAID, $attempt->fresh()->status);
        $this->assertLedgerMatchesBalance(1);
    }

    public function test_amount_or_currency_mismatch_is_rejected(): void
    {
        foreach ([['amount' => 1, 'currency' => 'SAR'], ['amount' => 100, 'currency' => 'USD']] as $charge) {
            $this->fakeTap(['status' => 'CAPTURED'] + $charge);
            $attempt = $this->attempt(100);

            $this->assertSame('failed', $this->gatewayCallback($attempt)['status']);
            $this->assertSame(PaymentAttempt::STATUS_FAILED, $attempt->fresh()->status);
        }

        $this->assertSame(0.0, $this->balances(1)['total']);
    }

    public function test_charge_id_from_another_attempt_cannot_be_replayed(): void
    {
        $this->fakeTap(['status' => 'CAPTURED', 'amount' => 100, 'currency' => 'SAR']);
        $attempt = $this->attempt(100);

        $this->assertSame('failed', $this->gatewayCallback($attempt, 'chg_someone_else')['status']);
        $this->assertSame(0.0, $this->balances(1)['total']);
    }

    public function test_cancelled_payment_adds_nothing(): void
    {
        $this->fakeTap(['status' => 'CANCELLED', 'amount' => 100, 'currency' => 'SAR']);
        $attempt = $this->attempt(100);

        $this->assertSame('cancelled', $this->gatewayCallback($attempt)['status']);
        $this->assertSame(0.0, $this->balances(1)['total']);
    }

    public function test_top_up_offer_adds_promotional_bonus_within_the_per_customer_limit(): void
    {
        $offer = WalletTopupOffer::create(['title_ar' => 'اشحن 500 واحصل على 50', 'min_amount' => 500, 'bonus_amount' => 50, 'bonus_expiry_days' => 30, 'max_uses_per_user' => 1]);
        $this->fakeTap(['status' => 'CAPTURED', 'amount' => 500, 'currency' => 'SAR']);

        $first = $this->attempt(500, ['offer_id' => $offer->id]);
        $this->gatewayCallback($first);
        $this->assertSame(['total' => 550.0, 'cash' => 500.0, 'bonus' => 50.0], $this->balances(1));
        $bonusRow = DB::table('wallet_histories')->where('source', 'campaign')->first();
        $this->assertNotNull($bonusRow->expires_at);

        DB::table('payment_attempts')->update(['external_id' => null]);
        $second = $this->attempt(500, ['offer_id' => $offer->id]);
        $this->gatewayCallback($second);
        $this->assertSame(['total' => 1050.0, 'cash' => 1000.0, 'bonus' => 50.0], $this->balances(1));
        $this->assertLedgerMatchesBalance(1);
    }
}
