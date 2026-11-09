<?php
namespace Tests\Feature;

use App\Models\User;
use App\Services\BookingArrival;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Modules\Booking\Models\Booking;
use Tests\TestCase;

class BookingArrivalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->softDeletes(); $table->timestamps();
        });
        Schema::create('settings', function (Blueprint $table) {
            $table->id(); $table->string('name')->unique(); $table->text('val')->nullable();
            $table->string('type')->default('string'); $table->softDeletes(); $table->timestamps();
        });
        Schema::create('bookings', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('user_id'); $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('status'); $table->dateTime('start_date_time')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable(); $table->softDeletes(); $table->timestamps();
        });
        Schema::create('booking_services', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('booking_id'); $table->unsignedBigInteger('service_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable(); $table->dateTime('start_date_time')->nullable();
            $table->timestamps();
        });
        Schema::create('booking_packages', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('booking_id'); $table->unsignedBigInteger('package_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable(); $table->timestamps();
        });
        (require database_path('migrations/2026_10_12_000002_add_arrival_to_bookings.php'))->up();
        DB::table('users')->insert([['id'=>1], ['id'=>2]]);
        DB::table('bookings')->insert(['id'=>10, 'user_id'=>2, 'status'=>'confirmed', 'start_date_time'=>now('Asia/Riyadh')->format('Y-m-d H:i:s')]);
        (require database_path('migrations/2023_01_01_000021_create_permission_tables.php'))->up();
        DB::table('roles')->insert([
            ['id'=>1, 'name'=>'admin', 'title'=>'Admin', 'guard_name'=>'web'],
            ['id'=>2, 'name'=>'user', 'title'=>'Customer', 'guard_name'=>'web'],
        ]);
        DB::table('permissions')->insert(['name'=>'edit_booking', 'guard_name'=>'web']);
        DB::table('model_has_roles')->insert([
            ['role_id'=>1, 'model_type'=>User::class, 'model_id'=>1],
            ['role_id'=>2, 'model_type'=>User::class, 'model_id'=>2],
        ]);
        // Keep real auth, permission and signature middleware; omit branch view setup.
        $this->withoutMiddleware(\App\Http\Middleware\BranchListCheck::class);
    }

    public function test_repeated_confirmation_preserves_original_time_and_actor_and_manual_toggle_can_reset(): void
    {
        $arrival = new BookingArrival;
        $booking = $arrival->update(10, true, 1, 'qr');
        $original = $booking->arrived_at->toIso8601String();
        $this->assertSame('confirmed', $booking->status);
        $this->travel(5)->minutes();
        $booking = $arrival->update(10, true, 2, 'manual');
        $this->assertSame($original, $booking->arrived_at->toIso8601String());
        $this->assertSame(1, $booking->arrival_confirmed_by);
        $this->assertSame('qr', $booking->arrival_source);
        $booking = $arrival->update(10, false, 1, 'manual');
        $this->assertNull($booking->arrived_at);
        $this->assertNull($booking->arrival_confirmed_by);
    }

    public function test_cancelled_and_finished_bookings_cannot_be_confirmed(): void
    {
        foreach (['cancelled', 'canceled', 'completed', 'checkout'] as $status) {
            DB::table('bookings')->where('id', 10)->update(['status'=>$status]);
            try {
                (new BookingArrival)->update(10, true, 1, 'manual');
                $this->fail('A closed booking must reject arrival changes.');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('arrived', $e->errors());
            }
            $this->assertNull(Booking::find(10)->arrived_at);
        }
    }

    public function test_customer_cannot_confirm_arrival_even_with_a_valid_qr(): void
    {
        $this->actingAs(User::find(2));
        $url = URL::signedRoute('backend.booking-arrival.scan', ['booking'=>10]);
        $this->postJson($url, ['arrived'=>true, 'source'=>'qr'])->assertForbidden();
        $this->assertNull(Booking::find(10)->arrived_at);
    }

    public function test_invalid_scan_signature_is_rejected_and_staff_can_confirm_manually_or_by_valid_qr(): void
    {
        $this->actingAs(User::find(1));
        $url = URL::signedRoute('backend.booking-arrival.scan', ['booking'=>10]);
        $this->postJson($url.'x', ['arrived'=>true, 'source'=>'qr'])->assertForbidden();
        $this->assertNull(Booking::find(10)->arrived_at);
        $this->post($url, ['arrived'=>true, 'source'=>'qr'])->assertRedirect();
        $this->assertSame('qr', Booking::find(10)->arrival_source);
        $this->post(route('backend.booking-arrival.update', 10), ['arrived'=>false, 'source'=>'manual'])->assertRedirect();
        $this->assertNull(Booking::find(10)->arrived_at);
        $this->post(route('backend.booking-arrival.update', 10), ['arrived'=>true, 'source'=>'qr'])->assertForbidden();
    }
}
