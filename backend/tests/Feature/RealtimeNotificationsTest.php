<?php
namespace Tests\Feature;
use App\Http\Controllers\RealtimeNotificationsController;
use App\Models\User;
use App\Observers\RealtimeNotificationObserver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
class RealtimeNotificationsTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        $this->assertSame('sqlite',DB::connection()->getDriverName());$this->assertSame(':memory:',DB::connection()->getDatabaseName());
        config(['realtime.enabled'=>true,'broadcasting.connections.pusher.key'=>'test-key','broadcasting.connections.pusher.secret'=>'test-secret','broadcasting.connections.pusher.app_id'=>'123','broadcasting.connections.pusher.options.cluster'=>'eu']);
        DatabaseNotification::flushEventListeners(); DatabaseNotification::observe(RealtimeNotificationObserver::class);
        Schema::create('notifications',function(Blueprint $t){$t->uuid('id')->primary();$t->string('type');$t->morphs('notifiable');$t->text('data');$t->timestamp('read_at')->nullable();$t->timestamps();});
    }
    private function createNotification(): DatabaseNotification {
        return DatabaseNotification::create(['id'=>(string)\Illuminate\Support\Str::uuid(),'type'=>'test','notifiable_type'=>(new User)->getMorphClass(),'notifiable_id'=>7,'data'=>['message'=>'Private contents']]);
    }
    private function request(array $data=[]): Request {
        $r=Request::create('/api/notifications/realtime-config','POST',$data);$user=new User();$user->id=7;$r->setUserResolver(fn()=>$user);return $r;
    }
    public function test_configuration_contains_no_secret_and_routes_require_authentication(): void {
        $response=(new RealtimeNotificationsController)->config($this->request());$data=$response->getData(true);
        $this->assertTrue($data['enabled']);$this->assertSame('private-notifications.user.7',$data['channel']);
        $this->assertArrayNotHasKey('secret',$data);$this->assertStringNotContainsString('test-secret',$response->getContent());
        $this->assertStringContainsString('no-store',$response->headers->get('Cache-Control'));
        $routes=app('router')->getRoutes();
        $this->assertContains('auth:sanctum',$routes->getByName('api.realtime.auth')->gatherMiddleware());
        $this->assertContains('auth',$routes->getByName('broadcasting.auth')->gatherMiddleware());
    }
    public function test_user_cannot_authorize_another_users_channel(): void {
        Broadcast::shouldReceive('connection')->never();
        try{(new RealtimeNotificationsController)->auth($this->request(['socket_id'=>'123.456','channel_name'=>'private-notifications.user.8']));$this->fail('Unauthorized channel allowed');}
        catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){$this->assertSame(403,$e->getStatusCode());}
    }
    public function test_own_channel_is_signed_by_the_actual_pusher_sdk_without_network_calls(): void {
        $response=(new RealtimeNotificationsController)->auth($this->request(['socket_id'=>'123.456','channel_name'=>'private-notifications.user.7']));
        $data=$response instanceof \Illuminate\Http\JsonResponse ? $response->getData(true) : (array)$response;
        $this->assertSame('test-key:'.hash_hmac('sha256','123.456:private-notifications.user.7','test-secret'),$data['auth']);
    }
    public function test_notification_is_published_only_after_commit_without_message_contents(): void {
        $driver=\Mockery::mock();Broadcast::shouldReceive('connection')->once()->with('pusher')->andReturn($driver);
        $driver->shouldReceive('broadcast')->once()->with(['private-notifications.user.7'],'notification.created',\Mockery::on(fn($payload)=>count($payload)===1 && isset($payload['id'])));
        DB::beginTransaction();$notification=$this->createNotification();$this->assertTrue($notification->exists);DB::commit();
    }
    public function test_rolled_back_notifications_are_not_published(): void {
        Broadcast::shouldReceive('connection')->never();DB::beginTransaction();$this->createNotification();DB::rollBack();
        $this->assertSame(0,DatabaseNotification::count());
    }
    public function test_missing_credentials_leave_notification_persistence_working(): void {
        config(['broadcasting.connections.pusher.key'=>'']);Broadcast::shouldReceive('connection')->never();
        $this->createNotification();$this->assertSame(1,DatabaseNotification::count());
        $this->assertFalse((new RealtimeNotificationsController)->config($this->request())->getData(true)['enabled']);
    }
    public function test_pusher_failure_does_not_break_notification_creation(): void {
        Broadcast::shouldReceive('connection')->once()->andThrow(new \RuntimeException('Simulated outage'));
        $this->createNotification();$this->assertSame(1,DatabaseNotification::count());
    }
}
