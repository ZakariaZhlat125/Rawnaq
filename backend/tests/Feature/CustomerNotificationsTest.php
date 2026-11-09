<?php

namespace Tests\Feature;

use App\Http\Controllers\Backend\API\NotificationsController;
use App\Models\LoyaltyPointTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Booking\Models\Booking;
use Modules\Product\Models\Order;
use Modules\Wallet\Models\WalletHistory;
use Tests\TestCase;

class CustomerNotificationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->softDeletes(); $table->timestamps();
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary(); $table->string('type');
            $table->morphs('notifiable'); $table->text('data');
            $table->timestamp('read_at')->nullable(); $table->timestamps();
        });
        Schema::create('bookings', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('user_id'); $table->string('status');
            $table->string('start_date_time')->nullable(); $table->softDeletes(); $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('user_id');
            $table->string('delivery_status'); $table->string('payment_status'); $table->timestamps();
        });
        Schema::create('booking_transactions', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('booking_id');
            $table->string('external_transaction_id'); $table->string('transaction_type');
            $table->integer('payment_status'); $table->timestamps();
        });
        Schema::create('booking_services', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('service_id'); $table->unsignedBigInteger('employee_id')->nullable();
            $table->timestamps();
        });
        Schema::create('wallet_histories', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('user_id');
            $table->string('activity_type'); $table->text('activity_data'); $table->timestamps();
        });
        Schema::create('loyalty_points_transactions', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('user_id'); $table->string('action');
            $table->integer('points'); $table->integer('balance_after');
            $table->text('meta')->nullable(); $table->timestamps();
        });
        DB::table('users')->insert([['id' => 1], ['id' => 2]]);
    }

    private function requestFor(int $id, array $input = []): Request
    {
        $request = Request::create('/api/notification-list', 'GET', $input);
        $request->setUserResolver(fn () => User::findOrFail($id));
        return $request;
    }

    public function test_booking_and_order_changes_are_recorded_once_and_unrelated_saves_are_ignored(): void
    {
        $booking = Booking::create(['user_id' => 1, 'status' => 'pending']);
        $booking->update(['status' => 'confirmed']);
        $booking->save();
        $booking->update(['start_date_time' => '2026-10-01 10:00:00']);
        $order = Order::unguarded(fn () => Order::create(['user_id' => 1, 'delivery_status' => 'pending', 'payment_status' => 'unpaid']));
        $order->delivery_status = 'delivered'; $order->save();
        $this->assertSame(5, User::find(1)->notifications()->count());
        $this->assertSame(0, User::find(2)->notifications()->count());
    }

    public function test_financial_notifications_rollback_with_the_transaction(): void
    {
        DB::beginTransaction();
        WalletHistory::create(['user_id' => 1, 'activity_type' => 'withdraw', 'activity_data' => json_encode(['credit_debit_amount' => 75])]);
        $this->assertSame(1, User::find(1)->notifications()->count());
        DB::rollBack();
        $this->assertSame(0, User::find(1)->notifications()->count());
        WalletHistory::create(['user_id' => 1, 'activity_type' => 'wheel_win', 'activity_data' => json_encode(['reward_value' => 25])]);
        $this->assertSame(25, User::find(1)->notifications()->first()->data['data']['amount']);
    }

    public function test_payment_recorder_notifies_each_booking_and_service_updates_reach_its_owner(): void
    {
        $booking = Booking::create(['user_id' => 1, 'status' => 'pending']);
        (new \App\Services\Payment\BookingTransactionRecorderService())->markBookingsPaid([$booking->id], 'test-payment', 'wallet');
        $this->assertSame('confirmed', $booking->fresh()->status);
        $this->assertSame(3, User::find(1)->notifications()->count());
        $service = \Modules\Booking\Models\BookingService::create(['booking_id' => $booking->id, 'service_id' => 7]);
        $service->update(['employee_id' => 12]);
        $service->delete();
        $this->assertSame(6, User::find(1)->notifications()->count());
        $this->assertSame(0, User::find(2)->notifications()->count());
    }

    public function test_loyalty_credit_debit_expiry_and_zero_reward(): void
    {
        foreach ([['add', 10, 10, null], ['deduct', 4, 6, null], ['deduct', 6, 0, ['reason' => 'Points expired']], ['add', 0, 0, null]] as [$action, $points, $balance, $meta]) {
            LoyaltyPointTransaction::create(['user_id' => 1, 'action' => $action, 'points' => $points, 'balance_after' => $balance, 'meta' => $meta]);
        }
        $entries = User::find(1)->notifications()->get();
        $this->assertCount(3, $entries);
        $this->assertTrue($entries->contains(fn ($entry) => $entry->data['subject'] === 'انتهاء صلاحية نقاط ولاء'));
    }

    public function test_reading_and_marking_are_scoped_to_the_current_customer(): void
    {
        Booking::create(['user_id' => 1, 'status' => 'pending']);
        Booking::create(['user_id' => 2, 'status' => 'pending']);
        $controller = new NotificationsController();
        $request = $this->requestFor(1);
        $payload = $controller->index($request);
        $this->assertCount(1, $payload['notification_data']);
        $this->assertSame(1, $payload['all_unread_count']);
        $controller->markRead($request, $payload['notification_data'][0]->id);
        $this->assertSame(0, $controller->index($request)['all_unread_count']);
        $controller->markAllRead($request);
        $this->assertSame(1, User::find(2)->unreadNotifications()->count());
        $this->expectException(ModelNotFoundException::class);
        $controller->markRead($request, User::find(2)->notifications()->first()->id);
    }
}
