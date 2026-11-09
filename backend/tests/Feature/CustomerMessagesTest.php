<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\CustomerMessages;
use App\Services\TaqnyatSmsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerMessagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $this->travelTo(now()->setDate(2026, 10, 12)->setTime(9, 0));
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('first_name'); $table->string('last_name');
            $table->string('mobile')->nullable(); $table->date('date_of_birth')->nullable();
            $table->boolean('status')->default(1); $table->boolean('is_banned')->default(0);
            $table->softDeletes(); $table->timestamps();
        });
        Schema::create('roles', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('guard_name'); $table->timestamps();
        });
        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id'); $table->string('model_type'); $table->unsignedBigInteger('model_id');
        });
        Schema::create('settings', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->text('val'); $table->string('type');
            $table->softDeletes(); $table->timestamps();
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary(); $table->string('type'); $table->morphs('notifiable');
            $table->text('data'); $table->timestamp('read_at')->nullable(); $table->timestamps();
        });
        Schema::create('ouroffersections', function (Blueprint $table) {
            $table->id(); $table->text('title'); $table->text('description');
            $table->string('discount_type'); $table->decimal('discount_value');
            $table->date('start_date'); $table->date('end_date'); $table->string('color');
            $table->string('link')->nullable(); $table->boolean('overlay'); $table->boolean('status'); $table->timestamps();
        });
        (require database_path('migrations/2026_10_12_000001_create_customer_messages_tables.php'))->up();
        DB::table('roles')->insert([['id'=>1, 'name'=>'user', 'guard_name'=>'web'], ['id'=>2, 'name'=>'admin', 'guard_name'=>'web']]);
        foreach ([1,2,3,4,5,6,7] as $id) {
            DB::table('users')->insert([
                'id'=>$id, 'first_name'=>'عميل', 'last_name'=>(string) $id,
                'mobile'=>$id === 7 ? null : '96650000000'.$id,
                'date_of_birth'=>$id === 2 ? '1990-10-13' : ($id === 6 ? '2000-10-12' : '1990-10-12'),
                'is_banned'=>$id === 4, 'status'=>$id === 5 ? 0 : 1,
            ]);
            DB::table('model_has_roles')->insert(['role_id'=>$id === 3 ? 2 : 1, 'model_type'=>User::class, 'model_id'=>$id]);
        }
        Setting::flushCache();
    }

    public function test_birthdays_match_day_and_month_and_do_not_repeat(): void
    {
        $messages = new CustomerMessages;
        $first = $messages->birthdays();
        $this->assertSame($first, $messages->birthdays());
        $this->assertEquals([1,6,7], DB::table('customer_message_deliveries')->orderBy('user_id')->pluck('user_id')->all());
        $this->assertSame(1, DB::table('customer_message_campaigns')->count());
        DB::table('settings')->insert(['name'=>'birthday_messages_enabled', 'val'=>'0', 'type'=>'string']);
        Setting::flushCache();
        $this->assertNull($messages->birthdays());
    }

    public function test_selected_campaign_is_sent_immediately_once_and_does_not_send_other_campaigns(): void
    {
        $messages = new CustomerMessages;
        $other = $messages->create('مناسبة أخرى', 'لا ترسل الآن', $messages->customers()->where('users.id', 2));
        $id = $messages->create('العيد', 'أهلاً {name} بمناسبة {occasion}', $messages->customers()->where('users.id', 1));
        $gateway = \Mockery::mock(TaqnyatSmsService::class);
        $gateway->shouldReceive('sendWhatsApp')->once()->with('966500000001', 'أهلاً عميل 1 بمناسبة العيد')->andReturn(['status'=>'success']);
        $this->assertSame(1, $messages->sendCampaign($id, $gateway));
        $this->assertSame(0, $messages->sendCampaign($id, $gateway));
        $this->assertSame('pending', DB::table('customer_message_deliveries')->where('campaign_id', $other)->value('status'));
        $this->assertSame('sent', DB::table('customer_message_deliveries')->where('campaign_id', $id)->value('status'));
    }

    public function test_failed_missing_phone_and_local_simulation_are_distinguished(): void
    {
        $messages = new CustomerMessages;
        $id = $messages->create('اختبار', 'مرحباً', $messages->customers()->whereIn('users.id', [1,6,7]));
        $gateway = \Mockery::mock(TaqnyatSmsService::class);
        $gateway->shouldReceive('sendWhatsApp')->once()->with('966500000001', 'مرحباً')->andReturn(false);
        $gateway->shouldReceive('sendWhatsApp')->once()->with('966500000006', 'مرحباً')->andReturn(['status'=>'bypassed_local']);
        $this->assertSame(3, $messages->sendCampaign($id, $gateway));
        $this->assertEquals(['failed','simulated','failed'], DB::table('customer_message_deliveries')->orderBy('user_id')->pluck('status')->all());
        $this->assertSame(0, $messages->sendCampaign($id, $gateway));
    }

    public function test_customers_who_become_inactive_before_sending_are_skipped(): void
    {
        $messages = new CustomerMessages;
        $id = $messages->create('اختبار', 'مرحباً', $messages->customers()->where('users.id', 1));
        DB::table('users')->where('id', 1)->update(['is_banned'=>1]);
        $gateway = \Mockery::mock(TaqnyatSmsService::class);
        $gateway->shouldNotReceive('sendWhatsApp');
        $messages->sendCampaign($id, $gateway);
        $this->assertSame('failed', DB::table('customer_message_deliveries')->value('status'));
    }

    public function test_dashboard_sends_occasion_immediately_and_ignores_old_schedule_input(): void
    {
        $gateway = \Mockery::mock(TaqnyatSmsService::class);
        $gateway->shouldReceive('sendWhatsApp')->once()->with('966500000001', 'أهلاً عميل 1')->andReturn(['status'=>'success']);
        $request = Request::create('/app/customer-messages', 'POST', [
            'occasion'=>'العيد', 'message'=>'أهلاً {name}', 'audience'=>'selected', 'customer_ids'=>[1],
            'send_at'=>now()->addMonth()->toDateTimeString(),
        ]);
        (new \App\Http\Controllers\Backend\CustomerMessagesController)->store($request, new CustomerMessages, $gateway);
        $this->assertSame('sent', DB::table('customer_message_deliveries')->value('status'));
        $this->assertSame(now()->toDateTimeString(), DB::table('customer_message_campaigns')->value('send_at'));
    }

    public function test_birthday_command_sends_only_today_birthdays_and_never_pending_occasions(): void
    {
        $messages = new CustomerMessages;
        $occasion = $messages->create('مناسبة قديمة', 'لا ترسل', $messages->customers()->where('users.id', 2));
        $gateway = \Mockery::mock(TaqnyatSmsService::class);
        foreach ([1,6] as $id) {
            $gateway->shouldReceive('sendWhatsApp')->once()->with('96650000000'.$id,
                strtr(CustomerMessages::BIRTHDAY_MESSAGE, ['{name}'=>'عميل '.$id]))->andReturn(['status'=>'success']);
        }
        $this->instance(TaqnyatSmsService::class, $gateway);
        $this->artisan('customers:send-messages')->assertExitCode(0);
        $this->artisan('customers:send-messages')->assertExitCode(0);
        $this->assertSame('pending', DB::table('customer_message_deliveries')->where('campaign_id', $occasion)->value('status'));
        $this->assertSame(2, DB::table('customer_message_deliveries')->where('status', 'sent')->count());
    }

    public function test_new_offer_notifies_only_active_customers_in_arabic_and_edit_does_not_repeat(): void
    {
        $controller = new \App\Http\Controllers\Backend\OfferBannerController;
        $request = \Illuminate\Http\Request::create('/app/offer-banners', 'POST', [
            'title_ar'=>'عرض العناية', 'title_en'=>'Care offer',
            'desc_ar'=>'خصم خاص لعملائنا', 'desc_en'=>'Special discount',
            'discount_type'=>'percent', 'discount_value'=>20,
            'start_date'=>'2026-10-12', 'end_date'=>'2026-10-30',
        ]);
        $controller->store($request);
        foreach ([1,2,6,7] as $id) {
            $notification = User::find($id)->notifications()->first();
            $this->assertSame('عرض جديد من رونق', $notification->data['subject']);
            $this->assertStringContainsString('عرض العناية', $notification->data['data']['message']);
            $this->assertStringNotContainsString('Care offer', $notification->data['data']['message']);
        }
        foreach ([3,4,5] as $id) $this->assertSame(0, User::find($id)->notifications()->count());
        $controller->update($request, \App\Models\Ouroffersection::first()->id);
        $this->assertSame(4, DB::table('notifications')->count());
    }
}
