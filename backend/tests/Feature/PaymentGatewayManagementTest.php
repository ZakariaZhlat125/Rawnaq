<?php

namespace Tests\Feature;

use App\Http\Middleware\BranchListCheck;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\PaymentAttempt;
use App\Models\PaymentGateway;
use App\Models\User;
use App\Services\Payment\Gateways\ArbGateway;
use App\Services\Payment\Gateways\GatewayConfig;
use App\Services\Payment\PaymentGatewayManager;
use Database\Seeders\PaymentGatewaySeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PaymentGatewayManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('first_name')->nullable(); $table->string('last_name')->nullable();
            $table->string('email')->nullable(); $table->string('mobile')->nullable(); $table->string('password')->nullable(); $table->string('referral_code')->nullable();
            $table->boolean('status')->default(true); $table->softDeletes(); $table->timestamps();
        });
        Schema::create('settings', function (Blueprint $table) {
            $table->id(); $table->string('name')->nullable(); $table->text('val')->nullable();
            $table->string('type', 90)->default('string'); $table->softDeletes(); $table->timestamps();
        });
        (require database_path('migrations/2023_01_01_000021_create_permission_tables.php'))->up();
        foreach ([
            '2026_03_03_000000_create_payment_attempts_table.php',
            '2026_10_17_000005_add_purpose_to_payment_attempts.php',
            '2026_10_24_000001_create_payment_gateways_tables.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        DB::table('users')->insert(['id' => 1, 'first_name' => 'Sara']);
        Permission::create(['name' => 'manage_payment_gateways', 'guard_name' => 'web']);
        $this->withoutMiddleware([BranchListCheck::class, SetLocale::class, VerifyCsrfToken::class]);
    }

    private function seedFromEnv(array $config = []): void
    {
        config(array_merge([
            'services.tap.secret_key' => null, 'tabby.secret_key' => null, 'tabby.merchant_code' => null,
            'tamara.secret_key' => null, 'services.telr.store_id' => null, 'services.telr.auth_key' => null,
            'arb.tranportal_id' => null, 'arb.tranportal_password' => null, 'arb.resource_key' => null,
        ], $config));
        (new PaymentGatewaySeeder())->run();
    }

    private function gateway(string $code): PaymentGateway
    {
        return PaymentGateway::with('fields')->where('code', $code)->firstOrFail();
    }

    private function field(string $code, string $key)
    {
        return $this->gateway($code)->fields->firstWhere('key', $key);
    }

    private function admin(bool $allowed = true): User
    {
        $user = User::create(['first_name' => 'Admin', 'email' => Str::random(8).'@example.com', 'status' => 1]);
        if ($allowed) {
            $user->givePermissionTo('manage_payment_gateways');
        }

        return $user;
    }

    private function attempt(string $gateway, string $status = PaymentAttempt::STATUS_REDIRECTED): PaymentAttempt
    {
        return PaymentAttempt::create([
            'token' => (string) Str::uuid(), 'user_id' => 1, 'gateway' => $gateway, 'currency' => 'SAR',
            'gross_amount' => 100, 'amount' => 100, 'cart_ids' => [], 'gift_ids' => [], 'submethods' => [],
            'status' => $status,
        ]);
    }

    public function test_seeder_copies_env_credentials_once_and_enables_only_complete_gateways(): void
    {
        $this->seedFromEnv([
            'services.tap.secret_key' => 'sk_env_tap',
            'arb.tranportal_id' => 'T1', 'arb.tranportal_password' => 'pw', 'arb.resource_key' => '12345678901234567890123456789012', 'arb.mode' => 'production',
        ]);

        $this->assertSame(['card', 'tabby', 'tamara', 'telr', 'arb', 'urpay'], PaymentGateway::ordered()->pluck('code')->all());
        $this->assertSame(['card', 'arb', 'urpay'], app(PaymentGatewayManager::class)->availableCodes());
        $this->assertSame('sk_env_tap', $this->field('card', 'secret_key')->value);
        $this->assertSame('JOSPA Store', $this->field('card', 'statement_descriptor')->value);
        $this->assertSame('live', $this->field('urpay', 'mode')->value);
        $this->assertSame(['secret_key', 'merchant_code'], $this->gateway('tabby')->missingFields());

        // Stored encrypted, never as plain text.
        $raw = DB::table('payment_gateway_fields')->where('id', $this->field('card', 'secret_key')->id)->value('value');
        $this->assertNotSame('sk_env_tap', $raw);
        $this->assertStringNotContainsString('sk_env_tap', $raw);

        // Running again (fresh install seeding, a later deploy) keeps what the admin saved.
        $this->field('card', 'secret_key')->update(['value' => 'sk_from_dashboard']);
        $this->gateway('card')->update(['is_active' => false]);
        $this->seedFromEnv(['services.tap.secret_key' => 'sk_env_changed']);
        $this->assertSame('sk_from_dashboard', $this->field('card', 'secret_key')->value);
        $this->assertFalse($this->gateway('card')->is_active);
        $this->assertSame(6, PaymentGateway::count());
    }

    public function test_drivers_use_the_database_credentials(): void
    {
        $this->seedFromEnv(['tabby.secret_key' => 'sk_env', 'tabby.merchant_code' => 'ENV']);
        $this->field('tabby', 'secret_key')->update(['value' => 'sk_db']);
        $this->field('tabby', 'merchant_code')->update(['value' => 'MERCHANT_DB']);
        Http::fake(['api.tabby.ai/*' => Http::response(['id' => 'chk_1', 'configuration' => ['available_products' => ['installments' => [['web_url' => 'https://pay.example/chk_1']]]]])]);

        $result = app(PaymentGatewayManager::class)->forCheckout('tabby')
            ->create($this->attempt('tabby', PaymentAttempt::STATUS_INITIATED), ['name' => 'Sara', 'phone' => '0500000000'], ['success' => 's', 'fail' => 'f', 'cancel' => 'c']);

        $this->assertSame('https://pay.example/chk_1', $result['payment_url']);
        Http::assertSent(fn (HttpRequest $request) => $request->hasHeader('Authorization', 'Bearer sk_db')
            && $request['merchant_code'] === 'MERCHANT_DB');
    }

    public function test_disabled_or_incomplete_gateway_cannot_start_a_payment_but_can_still_confirm_one(): void
    {
        $this->seedFromEnv(['services.tap.secret_key' => 'sk_env_tap']);
        $manager = app(PaymentGatewayManager::class);

        $this->gateway('card')->update(['is_active' => false]);
        $this->assertFalse($manager->isAvailable('card'));
        $this->assertNotNull($manager->forCallback('card'), 'a payment started before the switch-off must still be verified');
        try {
            $manager->forCheckout('card');
            $this->fail('disabled gateway started a payment');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('not available', $e->getMessage());
        }

        $this->gateway('tabby')->update(['is_active' => true]);
        $this->assertFalse($manager->isAvailable('tabby'), 'enabled but incomplete is not offered at checkout');

        // A value that no longer decrypts (APP_KEY changed) counts as missing.
        $this->gateway('card')->update(['is_active' => true]);
        DB::table('payment_gateway_fields')->where('id', $this->field('card', 'secret_key')->id)->update(['value' => 'not-encrypted']);
        $this->assertNull($this->field('card', 'secret_key')->value);
        $this->assertFalse($manager->isAvailable('card'));

        $this->expectExceptionMessage('missing secret_key, merchant_code');
        $manager->forCheckout('tabby');
    }

    public function test_arb_attempt_token_is_read_from_trandata_with_the_database_key(): void
    {
        $key = '12345678901234567890123456789012';
        $this->seedFromEnv(['arb.tranportal_id' => 'T1', 'arb.tranportal_password' => 'pw', 'arb.resource_key' => $key]);
        $driver = app(PaymentGatewayManager::class)->forCallback('urpay');
        $this->assertInstanceOf(ArbGateway::class, $driver);

        $encrypt = \Closure::bind(fn (string $s, string $k) => $this->encrypt($s, $k), $driver, ArbGateway::class);
        $trandata = urldecode($encrypt(urlencode(json_encode([['udf1' => 'attempt-token', 'result' => 'CAPTURED']])), $key));

        $this->assertSame('attempt-token', $driver->attemptToken($trandata));
        $this->assertNull((new ArbGateway(new GatewayConfig($this->gateway('arb')->setRelation('fields', collect()))))->attemptToken($trandata));
    }

    public function test_only_admins_with_the_permission_can_manage_gateways(): void
    {
        $this->seedFromEnv();
        $gateway = $this->gateway('tabby');

        $this->actingAs($this->admin(false))
            ->putJson(route('backend.payment-gateways.update', $gateway), ['name_ar' => 'x'])
            ->assertForbidden();
    }

    public function test_admin_updates_credentials_keeping_blank_secrets_and_cannot_enable_an_incomplete_gateway(): void
    {
        $this->seedFromEnv(['tabby.secret_key' => 'sk_saved']);
        $gateway = $this->gateway('tabby');
        $id = fn (string $key) => $this->field('tabby', $key)->id;
        $this->actingAs($this->admin());

        // Enabling without the merchant code is refused and nothing is saved.
        $this->from('/back')->put(route('backend.payment-gateways.update', $gateway), [
            'name_ar' => 'تابي', 'is_active' => 1,
            'fields' => [$id('secret_key') => '', $id('merchant_code') => ''],
        ])->assertRedirect('/back')->assertSessionHasErrors('fields.'.$id('merchant_code'));
        $this->assertFalse($this->gateway('tabby')->is_active);

        // A blank secret keeps the saved one; other fields take what was typed.
        $longKey = str_repeat('k', 4000);
        $this->put(route('backend.payment-gateways.update', $gateway), [
            'name_ar' => 'تابي', 'name_en' => 'Tabby', 'is_active' => 1,
            'fields' => [$id('secret_key') => '', $id('merchant_code') => "  M-42  ", $id('public_key') => $longKey, $id('base_url') => 'https://api.tabby.ai'],
        ])->assertSessionHasNoErrors();

        $this->assertTrue($this->gateway('tabby')->is_active);
        $this->assertSame('sk_saved', $this->field('tabby', 'secret_key')->value);
        $this->assertSame('M-42', $this->field('tabby', 'merchant_code')->value);
        $this->assertSame($longKey, $this->field('tabby', 'public_key')->value);

        // Clearing a required secret of a live gateway is refused.
        $this->put(route('backend.payment-gateways.update', $gateway), [
            'name_ar' => 'تابي', 'is_active' => 1, 'clear' => [$id('secret_key') => 1],
            'fields' => [$id('merchant_code') => 'M-42', $id('base_url') => 'https://api.tabby.ai'],
        ])->assertSessionHasErrors('fields.'.$id('secret_key'));
        $this->assertSame('sk_saved', $this->field('tabby', 'secret_key')->value);

        // A select only takes one of its options.
        $this->put(route('backend.payment-gateways.update', $this->gateway('telr')), [
            'name_ar' => 'تلر', 'fields' => [$this->field('telr', 'test_mode')->id => 'maybe'],
        ])->assertSessionHasErrors('fields.'.$this->field('telr', 'test_mode')->id);
    }

    public function test_admin_adds_a_gateway_for_an_existing_driver_and_manages_its_fields(): void
    {
        $this->seedFromEnv();
        $this->actingAs($this->admin());

        $this->post(route('backend.payment-gateways.store'), ['code' => 'cod', 'driver' => 'arb', 'name_ar' => 'x'])
            ->assertSessionHasErrors('code');
        $this->post(route('backend.payment-gateways.store'), ['code' => 'arb2', 'driver' => 'unknown', 'name_ar' => 'x'])
            ->assertSessionHasErrors('driver');

        // An emptied "order" box arrives as null and must not reach the NOT NULL column.
        $response = $this->post(route('backend.payment-gateways.store'), ['code' => 'arb2', 'driver' => 'arb', 'name_ar' => 'الراجحي ٢', 'sort_order' => '']);
        $gateway = $this->gateway('arb2');
        $response->assertRedirect(route('backend.payment-gateways.edit', $gateway));
        $this->assertFalse($gateway->is_active);
        $this->assertSame(array_keys(ArbGateway::fields()), $gateway->fields->pluck('key')->all());
        $this->assertSame('682', $gateway->credentials()['currency_code']);

        $this->post(route('backend.payment-gateways.fields.store', $gateway), [
            'key' => 'merchant_category', 'label' => 'Category', 'type' => 'select', 'options' => "a=Alpha\nb",
        ])->assertSessionHasNoErrors();
        $this->assertSame(['a' => 'Alpha', 'b' => 'b'], $this->field('arb2', 'merchant_category')->options);

        $this->delete(route('backend.payment-gateways.fields.destroy', [$gateway, $this->field('arb2', 'resource_key')]))
            ->assertSessionHas('error');
        $this->assertNotNull($this->field('arb2', 'resource_key'));
        $this->delete(route('backend.payment-gateways.fields.destroy', [$gateway, $this->field('arb2', 'merchant_category')]));
        $this->assertNull($this->field('arb2', 'merchant_category'));

        // Another gateway's field is not reachable through this gateway's URL.
        $this->deleteJson(route('backend.payment-gateways.fields.destroy', [$gateway, $this->field('card', 'statement_descriptor')]))
            ->assertNotFound();
    }

    public function test_gateway_with_payments_in_progress_cannot_be_deleted(): void
    {
        $this->seedFromEnv();
        $this->actingAs($this->admin());
        $attempt = $this->attempt('telr');

        $this->delete(route('backend.payment-gateways.destroy', $this->gateway('telr')))->assertSessionHas('error');
        $this->assertNotNull(PaymentGateway::where('code', 'telr')->first());

        $attempt->update(['status' => PaymentAttempt::STATUS_PAID]);
        $this->delete(route('backend.payment-gateways.destroy', $this->gateway('telr')))
            ->assertRedirect(route('backend.payment-gateways.index'));
        $this->assertNull(PaymentGateway::where('code', 'telr')->first());
        $this->assertSame(0, DB::table('payment_gateway_fields')->whereNotIn('payment_gateway_id', PaymentGateway::pluck('id'))->count());
    }

    public function test_pages_render_one_textarea_per_credential_and_never_print_saved_secrets(): void
    {
        // The real admin layout needs the whole schema; render the pages inside a bare one.
        $stub = storage_path('framework/testing/payment-gateway-views');
        File::ensureDirectoryExists($stub.'/backend/layouts');
        File::put($stub.'/backend/layouts/app.blade.php', "@stack('after-styles') @yield('content')");
        View::getFinder()->prependLocation($stub);
        // CartCountComposer runs on every view and reads the booking tables.
        $this->app->instance(\App\Http\View\Composers\CartCountComposer::class, new class { public function compose($view): void {} });

        $this->seedFromEnv(['tabby.secret_key' => 'sk_live_super_secret_1234', 'tabby.merchant_code' => 'MERCHANT_9']);
        $this->actingAs($this->admin());
        $tabby = $this->gateway('tabby');

        $this->get(route('backend.payment-gateways.index'))->assertOk()
            ->assertSee('tabby')->assertSee(route('backend.payment-gateways.edit', $tabby), false);

        $html = $this->get(route('backend.payment-gateways.edit', $tabby))->assertOk()->getContent();

        foreach ($tabby->fields as $field) {
            $this->assertStringContainsString('<textarea name="fields['.$field->id.']"', $html, $field->key.' has its own textarea');
        }
        $this->assertStringNotContainsString('sk_live_super_secret_1234', $html);
        $this->assertStringContainsString('••••1234', $html);
        $this->assertStringContainsString('MERCHANT_9', $html, 'non-secret values are shown for editing');

        $telr = $this->get(route('backend.payment-gateways.edit', $this->gateway('telr')))->assertOk()->getContent();
        $this->assertStringContainsString('<select name="fields['.$this->field('telr', 'test_mode')->id.']"', $telr);

        File::deleteDirectory($stub);
    }
}
