<?php
namespace Tests\Feature;
use App\Http\Controllers\BrowserPushController;
use App\Jobs\SendBrowserPush;
use App\Models\BrowserPushSubscription;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;
class BrowserPushTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        config(['webpush.enabled' => true]);
        Schema::create('users', function (Blueprint $table) { $table->id(); $table->boolean('status')->default(1); $table->boolean('is_banned')->default(0); $table->softDeletes(); $table->timestamps(); });
        Schema::create('notifications', function (Blueprint $table) { $table->uuid('id')->primary(); $table->string('type'); $table->morphs('notifiable'); $table->text('data'); $table->timestamp('read_at')->nullable(); $table->timestamps(); });
        (require database_path('migrations/2026_10_15_000001_create_browser_push_subscriptions.php'))->up();
        DB::table('users')->insert([['id' => 1], ['id' => 2]]);
        Route::post('/push-test', [BrowserPushController::class, 'store'])->middleware('auth');
        Route::delete('/push-test', [BrowserPushController::class, 'destroy'])->middleware('auth');
    }
    private function payload(): array
    {
        return ['endpoint' => 'https://fcm.googleapis.com/fcm/send/test-device', 'keys' => ['p256dh' => str_repeat('a', 87), 'auth' => str_repeat('b', 22)]];
    }
    public function test_subscription_requires_authentication(): void
    {
        $this->postJson('/push-test', $this->payload())->assertUnauthorized();
    }
    public function test_subscription_is_idempotent_and_cannot_be_claimed_or_deleted_by_other_user(): void
    {
        $this->actingAs(User::find(1))->postJson('/push-test', $this->payload())->assertOk();
        $this->postJson('/push-test', $this->payload())->assertOk();
        $this->assertSame(1, BrowserPushSubscription::count());
        $this->actingAs(User::find(2))->postJson('/push-test', $this->payload())->assertStatus(409);
        $this->deleteJson('/push-test', $this->payload())->assertOk();
        $this->assertSame(1, BrowserPushSubscription::count());
        $this->actingAs(User::find(1))->deleteJson('/push-test', $this->payload())->assertOk();
        $this->assertSame(0, BrowserPushSubscription::count());
    }
    public function test_unsafe_endpoints_are_rejected(): void
    {
        $this->actingAs(User::find(1));
        foreach (['http://fcm.googleapis.com/test', 'https://127.0.0.1/test', 'https://fcm.googleapis.com.evil.test/test', 'https://fcm.googleapis.com:8443/test', 'https://user@fcm.googleapis.com/test'] as $endpoint) {
            $this->postJson('/push-test', array_replace($this->payload(), ['endpoint' => $endpoint]))->assertJsonValidationErrors('endpoint');
        }
    }
    public function test_new_inbox_notifications_schedule_push_but_read_updates_do_not(): void
    {
        Bus::fake();
        $notification = User::find(1)->notifications()->create(['id' => (string) Str::uuid(), 'type' => 'booking', 'data' => ['subject' => 'Test', 'data' => ['message' => 'Hello']]]);
        $notification->markAsRead();
        Bus::assertDispatchedTimes(SendBrowserPush::class, 1);
        Bus::assertDispatched(SendBrowserPush::class, fn ($job) => $job->notificationId === $notification->id && $job->afterCommit === true && $job->queue === 'webpush');
    }
    public function test_disabled_feature_does_not_schedule_push(): void
    {
        config(['webpush.enabled' => false]); Bus::fake();
        User::find(1)->notifications()->create(['id' => (string) Str::uuid(), 'type' => 'booking', 'data' => []]);
        Bus::assertNotDispatched(SendBrowserPush::class);
        $this->actingAs(User::find(1))->postJson('/push-test', $this->payload())->assertStatus(503);
    }
}
