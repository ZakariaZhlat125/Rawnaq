<?php

namespace Tests\Feature;

use App\Observers\BookingWhatsAppObserver;
use App\Services\BookingWhatsAppService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Modules\Booking\Models\Booking;
use Tests\TestCase;

class BookingWhatsAppTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        config(['booking_whatsapp.enabled' => true, 'booking_whatsapp.api_key' => 'test-token',
            'booking_whatsapp.timezone' => 'Asia/Riyadh']);
        Carbon::setTestNow(Carbon::parse('2026-10-03 09:00:00', 'UTC'));
        Http::preventStrayRequests();
        Http::fake(['api.taqnyat.sa/*' => Http::response(['statuses' => ['message_id' => 'wamid.test']], 200)]);

        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('first_name'); $table->string('last_name');
            $table->string('mobile')->nullable(); $table->softDeletes(); $table->timestamps();
        });
        Schema::create('branches', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->softDeletes(); $table->timestamps();
        });
        Schema::create('bookings', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable(); $table->string('status');
            $table->dateTime('start_date_time')->nullable(); $table->text('note')->nullable();
            $table->string('location')->nullable(); $table->unsignedBigInteger('deleted_by')->nullable();
            $table->softDeletes(); $table->timestamps();
        });
        Schema::create('booking_services', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('booking_id'); $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('employee_id'); $table->dateTime('start_date_time');
            $table->integer('duration_min'); $table->decimal('service_price');
        });
        Schema::create('booking_packages', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('booking_id'); $table->unsignedBigInteger('package_id');
            $table->unsignedBigInteger('employee_id');
        });
        (require database_path('migrations/2026_10_03_000001_create_booking_whatsapp_notifications_table.php'))->up();
        DB::table('users')->insert(['id' => 1, 'first_name' => 'أحمد', 'last_name' => 'محمد', 'mobile' => '0501234567']);
        DB::table('branches')->insert(['id' => 1, 'name' => json_encode(['ar' => 'فرع الرياض', 'en' => 'Riyadh'])]);
        // Isolate the notification lifecycle from unrelated loyalty/inbox observers.
        Booking::flushEventListeners();
        Booking::observe(BookingWhatsAppObserver::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function booking(array $attributes = []): Booking
    {
        return Booking::create(array_merge(['user_id' => 1, 'branch_id' => 1,
            'status' => 'confirmed', 'start_date_time' => '2026-10-04 18:00:00'], $attributes));
    }

    private function runSender(): void
    {
        $this->artisan('bookings:send-whatsapp')->assertSuccessful();
    }

    public function test_confirmed_creation_uses_whatsapp_template_and_customer_number_once(): void
    {
        $booking = $this->booking();
        $this->runSender();
        $this->runSender();
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.taqnyat.sa/wa/v2/messages/'
            && $request['to'] === '966501234567'
            && $request['template']['name'] === 'sami_booking_created'
            && $request['components'][0]['parameters'] === [
                ['type' => 'text', 'text' => 'أحمد محمد'], ['type' => 'text', 'text' => (string) $booking->id],
                ['type' => 'text', 'text' => '2026-10-04'], ['type' => 'text', 'text' => '18:00'],
                ['type' => 'text', 'text' => 'فرع الرياض'],
            ]);
        $this->assertDatabaseHas('booking_whatsapp_notifications', ['event' => 'created', 'status' => 'sent']);
    }

    public function test_pending_cart_waits_for_confirmation_and_duplicate_callbacks_do_not_repeat_creation(): void
    {
        $booking = $this->booking(['status' => 'pending']);
        $this->runSender();
        Http::assertNothingSent();
        $booking->update(['status' => 'confirmed']);
        $booking->update(['status' => 'confirmed']);
        $this->runSender();
        Http::assertSentCount(1);
        $this->assertDatabaseCount('booking_whatsapp_notifications', 1);
    }

    public function test_rolled_back_creation_never_sends(): void
    {
        DB::beginTransaction();
        $this->booking();
        DB::rollBack();
        $this->runSender();
        Http::assertNothingSent();
        $this->assertDatabaseCount('booking_whatsapp_notifications', 0);
    }

    public function test_reminder_is_sent_at_six_hours_in_riyadh_time_and_only_once(): void
    {
        $this->booking(['start_date_time' => '2026-10-03 18:01:00']);
        $this->runSender();
        $this->assertDatabaseMissing('booking_whatsapp_notifications', ['event' => 'reminder']);
        Carbon::setTestNow(Carbon::parse('2026-10-03 09:01:00', 'UTC'));
        $this->runSender();
        $this->runSender();
        Http::assertSentCount(2);
        $this->assertDatabaseHas('booking_whatsapp_notifications', ['event' => 'reminder', 'status' => 'sent']);
    }

    public function test_cancellation_sends_cancellation_and_suppresses_queued_creation_and_reminder(): void
    {
        $booking = $this->booking(['start_date_time' => '2026-10-03 18:00:00']);
        app(BookingWhatsAppService::class)->enqueue($booking, 'reminder');
        $booking->update(['status' => 'cancelled']);
        $booking->update(['status' => 'cancelled']);
        $this->runSender();
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['template']['name'] === 'sami_booking_cancelled');
        $this->assertDatabaseHas('booking_whatsapp_notifications', ['event' => 'reminder', 'status' => 'skipped']);
    }

    public function test_rescheduling_invalidates_old_reminder_and_sends_new_one_at_new_time(): void
    {
        $booking = $this->booking(['start_date_time' => '2026-10-03 18:00:00']);
        app(BookingWhatsAppService::class)->enqueue($booking, 'reminder', 'old-reminder');
        $booking->update(['start_date_time' => '2026-10-03 20:00:00']);
        $this->runSender();
        $this->assertDatabaseHas('booking_whatsapp_notifications', ['deduplication_key' => 'old-reminder', 'status' => 'skipped']);
        Carbon::setTestNow(Carbon::parse('2026-10-03 11:00:00', 'UTC'));
        $this->runSender();
        $this->runSender();
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request['template']['name'] === 'sami_booking_reminder'
            && $request['components'][0]['parameters'][3]['text'] === '20:00');
    }

    public function test_past_completed_checked_in_and_pending_bookings_do_not_get_reminders(): void
    {
        foreach (['completed', 'check_in', 'pending', 'cancelled'] as $status) {
            $this->booking(['status' => $status, 'start_date_time' => '2026-10-03 18:00:00']);
        }
        $this->booking(['start_date_time' => '2026-10-03 12:00:00']);
        $this->runSender();
        Http::assertNothingSent();
        $this->assertDatabaseMissing('booking_whatsapp_notifications', ['event' => 'reminder']);
    }

    public function test_calendar_employee_only_update_sends_once_but_unchanged_save_does_not(): void
    {
        $booking = $this->booking();
        DB::table('booking_services')->insert(['booking_id' => $booking->id, 'service_id' => 1,
            'employee_id' => 10, 'start_date_time' => $booking->start_date_time, 'duration_min' => 30, 'service_price' => 100]);
        $service = app(BookingWhatsAppService::class);
        $before = $service->fingerprint($booking);
        $service->enqueueCalendarUpdate($booking, $before);
        $this->assertDatabaseMissing('booking_whatsapp_notifications', ['event' => 'updated']);
        DB::table('booking_services')->where('booking_id', $booking->id)->update(['employee_id' => 20]);
        $service->enqueueCalendarUpdate($booking, $before);
        $this->runSender();
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request['template']['name'] === 'sami_booking_updated');
    }

    public function test_failed_provider_response_retries_without_sms_fallback_and_records_success(): void
    {
        $this->booking();
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::preventStrayRequests();
        Http::fake(['api.taqnyat.sa/*' => Http::sequence()->push(['reason' => 'unavailable'], 503)
            ->push(['statuses' => ['message_id' => 'wamid.retry']], 200)]);
        $this->runSender();
        $this->runSender();
        Http::assertSentCount(1);
        $this->assertDatabaseHas('booking_whatsapp_notifications', ['status' => 'pending', 'attempts' => 1]);
        Carbon::setTestNow(now()->addMinutes(2));
        $this->runSender();
        Http::assertSentCount(2);
        $this->assertDatabaseHas('booking_whatsapp_notifications', ['status' => 'sent', 'attempts' => 2, 'provider_message_id' => 'wamid.retry']);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/v1/messages'));
    }

    public function test_http_200_without_provider_message_id_is_not_success(): void
    {
        $this->booking();
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::preventStrayRequests();
        Http::fake(['api.taqnyat.sa/*' => Http::response(['message' => '132000', 'reason' => 'Template mismatch'], 200)]);
        $this->runSender();
        $this->assertDatabaseHas('booking_whatsapp_notifications', ['status' => 'pending', 'attempts' => 1]);
        $this->assertDatabaseMissing('booking_whatsapp_notifications', ['status' => 'sent']);
    }

    public function test_missing_credentials_and_disabled_feature_never_send_or_claim_success(): void
    {
        config(['booking_whatsapp.enabled' => false]);
        $this->booking();
        $this->runSender();
        $this->assertDatabaseCount('booking_whatsapp_notifications', 0);
        config(['booking_whatsapp.enabled' => true, 'booking_whatsapp.api_key' => null]);
        $this->booking();
        $this->artisan('bookings:send-whatsapp')->assertFailed();
        Http::assertNothingSent();
        $this->assertDatabaseHas('booking_whatsapp_notifications', ['status' => 'pending', 'attempts' => 0]);
    }

    public function test_invalid_phone_is_not_sent(): void
    {
        $this->booking();
        DB::table('users')->where('id', 1)->update(['mobile' => 'invalid']);
        $this->runSender();
        Http::assertNothingSent();
        $this->assertDatabaseHas('booking_whatsapp_notifications', ['status' => 'pending', 'attempts' => 1]);
    }

    public function test_phone_normalization_supports_saudi_and_international_numbers(): void
    {
        $service = app(BookingWhatsAppService::class);
        foreach (['0501234567', '501234567', '+966501234567', '00966501234567', '٠٥٠١٢٣٤٥٦٧'] as $phone) {
            $this->assertSame('966501234567', $service->normalizePhone($phone));
        }
        $this->assertSame('201012345678', $service->normalizePhone('+20 101 234 5678'));
        $this->assertNull($service->normalizePhone('123'));
    }

    public function test_soft_deleted_confirmed_booking_sends_only_cancellation(): void
    {
        $booking = $this->booking(['start_date_time' => '2026-10-03 18:00:00']);
        $booking->delete();
        $this->runSender();
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['template']['name'] === 'sami_booking_cancelled');
        $this->assertDatabaseMissing('booking_whatsapp_notifications', ['event' => 'reminder']);
    }

    public function test_failed_calendar_edit_rolls_back_its_message(): void
    {
        $booking = $this->booking();
        $service = app(BookingWhatsAppService::class);
        $before = $service->fingerprint($booking);
        DB::beginTransaction();
        $booking->update(['start_date_time' => '2026-10-04 20:00:00']);
        $service->enqueueCalendarUpdate($booking, $before);
        DB::rollBack();
        $this->runSender();
        Http::assertSentCount(1);
        $this->assertDatabaseMissing('booking_whatsapp_notifications', ['event' => 'updated']);
    }

    public function test_failed_messages_stop_after_five_attempts(): void
    {
        $this->booking();
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::preventStrayRequests();
        Http::fake(['api.taqnyat.sa/*' => Http::response([], 503)]);
        for ($i = 0; $i < 6; $i++) {
            $this->runSender();
            Carbon::setTestNow(now()->addHour());
        }
        Http::assertSentCount(5);
        $this->assertDatabaseHas('booking_whatsapp_notifications', ['status' => 'failed', 'attempts' => 5]);
    }
}
