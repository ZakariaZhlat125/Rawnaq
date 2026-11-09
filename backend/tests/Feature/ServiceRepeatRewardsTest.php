<?php
namespace Tests\Feature;
use App\Services\ServiceRepeatRewards;
use App\Observers\ServiceRepeatBookingObserver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Booking\Models\Booking;
use Tests\TestCase;

class ServiceRepeatRewardsTest extends TestCase {
    protected function tearDown(): void {
        // Each suite builds a different minimal SQLite schema; do not leak Eloquent's
        // cached column list into the next suite's mass-assignment checks.
        $columns = new \ReflectionProperty(\Illuminate\Database\Eloquent\Model::class,'guardableColumns');
        $columns->setAccessible(true); $columns->setValue(null,[]);
        parent::tearDown();
    }
    protected function setUp(): void {
        parent::setUp();
        $this->assertSame('sqlite',DB::connection()->getDriverName());
        $this->assertSame(':memory:',DB::connection()->getDatabaseName());
        Booking::flushEventListeners(); Booking::observe(ServiceRepeatBookingObserver::class);
        Schema::create('users',function(Blueprint $t){$t->id();$t->softDeletes();});
        DB::table('users')->insert([['id'=>1],['id'=>2]]);
        Schema::create('services',function(Blueprint $t){$t->id();$t->text('name');$t->softDeletes();});
        DB::table('services')->insert([['id'=>1,'name'=>'{"ar":"خدمة","en":"Service"}'],['id'=>2,'name'=>'{"en":"Other"}']]);
        Schema::create('bookings',function(Blueprint $t){
            $t->id();$t->unsignedBigInteger('user_id');$t->unsignedBigInteger('branch_id')->default(1);
            $t->string('status');$t->decimal('home_service_fee')->default(0);$t->text('note')->nullable();$t->timestamps();$t->softDeletes();
        });
        Schema::create('booking_services',function(Blueprint $t){$t->id();$t->unsignedBigInteger('booking_id');$t->unsignedBigInteger('service_id');$t->decimal('service_price');$t->decimal('discount_amount')->default(0);});
        Schema::create('booking_transactions',function(Blueprint $t){$t->id();$t->unsignedBigInteger('booking_id');$t->integer('payment_status');});
        Schema::create('booking_packages',function(Blueprint $t){$t->id();$t->unsignedBigInteger('booking_id');});
        (require base_path('database/migrations/2026_10_24_000001_create_service_repeat_rewards.php'))->up();
        (require base_path('database/migrations/2026_10_24_000002_add_repeat_reward_evaluation_marker.php'))->up();
        DB::table('service_repeat_settings')->update(['enabled'=>true,'starts_at'=>now()->subDay()]);
        DB::table('service_repeat_rules')->insert(['id'=>1,'service_id'=>1,'enabled'=>true,'required_visits'=>2,'starts_at'=>now()->subDay(),'created_at'=>now(),'updated_at'=>now()]);
    }
    private function booking(bool $paid=true,int $price=100,int $service=1): Booking {
        $b=Booking::create(['user_id'=>1,'status'=>'confirmed']);
        DB::table('booking_services')->insert(['booking_id'=>$b->id,'service_id'=>$service,'service_price'=>$price]);
        if($paid)DB::table('booking_transactions')->insert(['booking_id'=>$b->id,'payment_status'=>1]);
        return $b;
    }
    private function earn(): int {
        $this->booking()->update(['status'=>'completed']);$this->booking()->update(['status'=>'completed']);
        return (int)DB::table('service_repeat_rewards')->latest('id')->value('id');
    }
    private function reserve(int $reward,Booking $booking,int $user=1,int $service=1): void {
        DB::transaction(fn()=>app(ServiceRepeatRewards::class)->reserve($user,$service,$reward,$booking));
    }
    public function test_completed_paid_visits_issue_repeating_rewards_once(): void {
        $b=$this->booking();$b->update(['status'=>'completed']);
        $b->update(['note'=>'Again']);$b->update(['status'=>'confirmed']);$b->update(['status'=>'completed']);
        $this->assertSame(1,DB::table('service_repeat_visits')->count());
        $this->assertSame(0,DB::table('service_repeat_rewards')->count());
        $this->booking()->update(['status'=>'completed']);
        $this->assertSame(1,DB::table('service_repeat_rewards')->count());
        $this->earn();$this->assertSame(2,DB::table('service_repeat_rewards')->count());
    }
    public function test_unpaid_cancelled_free_package_other_service_and_old_bookings_do_not_count(): void {
        $this->booking(false)->update(['status'=>'completed']);
        $this->booking()->update(['status'=>'cancelled']);
        $this->booking(true,0)->update(['status'=>'completed']);
        $this->booking(true,100,2)->update(['status'=>'completed']);
        $p=$this->booking();DB::table('booking_packages')->insert(['booking_id'=>$p->id]);$p->update(['status'=>'completed']);
        $old=$this->booking();DB::table('bookings')->where('id',$old->id)->update(['created_at'=>now()->subDays(2)]);$old->refresh()->update(['status'=>'completed']);
        $this->assertSame(0,DB::table('service_repeat_visits')->count());
    }
    public function test_reservation_rejects_other_owner_other_service_and_duplicate_use(): void {
        $id=$this->earn();$b=$this->booking(false);
        foreach([[2,1],[1,2]] as [$user,$service]){
            try{$this->reserve($id,$b,$user,$service);$this->fail('Invalid reward accepted');}
            catch(ValidationException $e){$this->assertArrayHasKey('repeat_reward_id',$e->errors());}
        }
        $this->reserve($id,$b);
        $this->assertSame($id,(int)$b->fresh()->service_repeat_reward_id);
        $this->expectException(ValidationException::class);$this->reserve($id,$this->booking(false));
    }
    public function test_cancel_and_expiration_return_unused_reward_but_completed_reward_cannot_be_reused(): void {
        $id=$this->earn();$b=$this->booking(false,0);$this->reserve($id,$b);
        $b->update(['status'=>'cancelled']);$this->assertNull(DB::table('service_repeat_rewards')->value('booking_id'));
        $next=$this->booking(false,0);$this->reserve($id,$next);
        app(ServiceRepeatRewards::class)->release([$next->id]);
        $this->assertNull(DB::table('service_repeat_rewards')->value('booking_id'));
        $last=$this->booking(false,0);$this->reserve($id,$last);$last->update(['status'=>'completed']);
        $this->assertNotNull(DB::table('service_repeat_rewards')->value('used_at'));
        $last->update(['status'=>'cancelled']);
        $this->assertSame($last->id,(int)DB::table('service_repeat_rewards')->value('booking_id'));
        $this->assertSame(2,DB::table('service_repeat_visits')->count());
    }
    public function test_cancelled_free_booking_cannot_be_reactivated_after_reward_is_returned(): void {
        $id=$this->earn();$b=$this->booking(false,0);$this->reserve($id,$b);$b->update(['status'=>'cancelled']);
        $this->expectException(ValidationException::class);$b->update(['status'=>'confirmed']);
    }
    public function test_global_and_service_switches_pause_earning_and_redemption(): void {
        $id=$this->earn();DB::table('service_repeat_settings')->update(['enabled'=>false]);
        $this->booking()->update(['status'=>'completed']);$this->assertSame(2,DB::table('service_repeat_visits')->count());
        $summary=app(ServiceRepeatRewards::class)->summary(1);$this->assertCount(0,$summary['progress']);$this->assertFalse($summary['rewards'][0]['usable']);
        try{$this->reserve($id,$this->booking());$this->fail('Paused reward accepted');}catch(ValidationException $e){$this->assertNotEmpty($e->errors());}
        DB::table('service_repeat_settings')->update(['enabled'=>true]);DB::table('service_repeat_rules')->update(['enabled'=>false]);
        $this->expectException(ValidationException::class);$this->reserve($id,$this->booking());
    }
    public function test_transaction_rollback_returns_reward_and_summary_is_private_to_owner(): void {
        $id=$this->earn();$b=$this->booking();
        try{DB::transaction(function()use($id,$b){$this->reserve($id,$b);throw new \RuntimeException('fail');});}catch(\RuntimeException $e){}
        $this->assertNull(DB::table('service_repeat_rewards')->value('booking_id'));
        $this->assertCount(0,app(ServiceRepeatRewards::class)->summary(2)['rewards']);
        $summary=app(ServiceRepeatRewards::class)->summary(1);$this->assertTrue($summary['rewards'][0]['usable']);
        $this->assertSame(0,$summary['progress'][0]['completed']);
    }
    public function test_dashboard_permissions_and_template(): void {
        $routes=app('router')->getRoutes();
        $this->assertContains('auth',$routes->getByName('backend.service-repeat.index')->gatherMiddleware());
        foreach(['store','toggle'] as $action)$this->assertContains('permission:store_loyalty',$routes->getByName('backend.service-repeat.'.$action)->gatherMiddleware());
        $compiled=app('blade.compiler')->compileString(file_get_contents(resource_path('views/backend/service-repeat/index.blade.php')));
        $this->assertNotEmpty(token_get_all($compiled,TOKEN_PARSE));
    }
    public function test_paused_completions_cannot_be_counted_later_by_editing_or_forging_marker(): void {
        DB::table('service_repeat_settings')->update(['enabled'=>false]);
        $booking=$this->booking();$stale=$booking->fresh();$booking->update(['status'=>'completed']);
        DB::table('service_repeat_settings')->update(['enabled'=>true]);
        $stale->update(['note'=>'Stale save','service_repeat_evaluated_at'=>null]);
        $booking->update(['note'=>'Retry after enable','service_repeat_evaluated_at'=>null]);
        $this->assertSame(0,DB::table('service_repeat_visits')->count());
        $this->assertNotNull($booking->fresh()->service_repeat_evaluated_at);
    }
    public function test_failed_award_rolls_back_visit_and_evaluation_then_normal_save_retries(): void {
        $this->booking()->update(['status'=>'completed']);
        DB::unprepared("CREATE TRIGGER fail_repeat_reward BEFORE INSERT ON service_repeat_rewards BEGIN SELECT RAISE(ABORT, 'Simulated reward failure'); END");
        $booking=$this->booking();
        try{$booking->update(['status'=>'completed']);$this->fail('Expected failed award');}
        catch(\Illuminate\Database\QueryException $e){$this->assertStringContainsString('Simulated reward failure',$e->getMessage());}
        $this->assertSame(1,DB::table('service_repeat_visits')->count());
        $this->assertNull($booking->fresh()->service_repeat_evaluated_at);
        DB::unprepared('DROP TRIGGER fail_repeat_reward');
        $booking->refresh()->update(['note'=>'Retry']);
        $this->assertSame(1,DB::table('service_repeat_rewards')->count());
        $this->assertSame(2,DB::table('service_repeat_visits')->count());
    }
}
