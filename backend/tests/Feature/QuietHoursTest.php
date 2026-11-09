<?php
namespace Tests\Feature;

use App\Models\BranchQuietHours;
use App\Models\LoyaltyPoint;
use App\Models\LoyaltyPointTransaction;
use App\Observers\QuietHoursBookingObserver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Booking\Models\Booking;
use Tests\TestCase;

class QuietHoursTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Booking::flushEventListeners();
        Booking::observe(QuietHoursBookingObserver::class);
        LoyaltyPointTransaction::flushEventListeners();
        Schema::create('users', function (Blueprint $t) { $t->id(); $t->softDeletes(); });
        DB::table('users')->insert(['id' => 1]);
        Schema::create('branches', fn (Blueprint $t) => $t->id());
        DB::table('branches')->insert([['id' => 1], ['id' => 2]]);
        Schema::create('bookings', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id'); $t->unsignedBigInteger('branch_id');
            $t->dateTime('start_date_time'); $t->string('status'); $t->text('note')->nullable();
            $t->unsignedBigInteger('home_city_id')->nullable();
            $t->timestamps(); $t->softDeletes();
        });
        (require base_path('database/migrations/2026_10_22_000002_add_quiet_hours_rewards.php'))->up();
        Schema::create('loyalty_points', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id')->unique(); $t->integer('points')->default(0); $t->timestamps();
        });
        Schema::create('loyalty_points_transactions', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id'); $t->string('action'); $t->integer('points');
            $t->integer('balance_after'); $t->string('source'); $t->unsignedBigInteger('source_id')->nullable();
            $t->text('meta')->nullable(); $t->timestamps();
        });
        BranchQuietHours::create(['branch_id' => 1, 'enabled' => true, 'points' => 20, 'hours' => [[12], [], [], [], [], [], []]]);
    }

    private function booking(array $overrides = []): Booking
    {
        return Booking::create(array_merge(['user_id' => 1, 'branch_id' => 1,
            'start_date_time' => '2026-10-25 12:30:00', 'status' => 'confirmed'], $overrides));
    }

    public function test_completion_awards_once_even_with_stale_models_and_repeated_status_changes(): void
    {
        $booking = $this->booking();
        $stale = Booking::find($booking->id);
        $this->assertSame(20, $booking->quiet_hours_points);
        $this->assertSame(0, LoyaltyPoint::count());
        $booking->update(['status' => 'completed']);
        $stale->update(['status' => 'completed']);
        $booking->refresh()->update(['note' => 'Edited']);
        $booking->update(['status' => 'confirmed']);
        $booking->update(['status' => 'completed']);
        $this->assertSame(20, (int) LoyaltyPoint::first()->points);
        $this->assertSame(1, LoyaltyPointTransaction::count());
        $this->assertSame('quiet_hours', LoyaltyPointTransaction::first()->source);
        $this->assertNotNull($booking->fresh()->quiet_hours_credited_at);
    }

    public function test_time_and_branch_changes_recalculate_but_settings_edits_preserve_the_snapshot(): void
    {
        $booking = $this->booking();
        BranchQuietHours::where('branch_id', 1)->update(['points' => 50]);
        $booking->update(['note' => 'Keep promised reward']);
        $this->assertSame(20, $booking->quiet_hours_points);
        $booking->update(['start_date_time' => '2026-10-25 13:00:00']);
        $this->assertSame(0, $booking->quiet_hours_points);
        BranchQuietHours::create(['branch_id' => 2, 'enabled' => true, 'points' => 70, 'hours' => [[13], [], [], [], [], [], []]]);
        $booking->update(['branch_id' => 2]);
        $this->assertSame(70, $booking->quiet_hours_points);
        BranchQuietHours::where('branch_id', 2)->update(['enabled' => false]);
        $booking->update(['status' => 'completed']);
        $this->assertSame(70, (int) LoyaltyPoint::first()->points);
        $this->assertSame(0, $this->booking(['branch_id' => 2])->quiet_hours_points);
    }

    public function test_cancelled_outside_hours_and_legacy_bookings_do_not_earn_points(): void
    {
        $this->booking(['status' => 'cancelled']);
        $this->booking(['status' => 'completed', 'home_city_id' => 42]);
        $this->booking(['status' => 'completed', 'start_date_time' => '2026-10-25 13:00:00']);
        $id = DB::table('bookings')->insertGetId(['user_id' => 1, 'branch_id' => 1,
            'start_date_time' => '2026-10-25 12:30:00', 'status' => 'confirmed']);
        Booking::find($id)->update(['status' => 'completed']);
        $this->assertSame(0, LoyaltyPointTransaction::count());
    }

    public function test_client_cannot_inject_points_or_forge_the_credit_marker(): void
    {
        $booking = $this->booking(['quiet_hours_points' => 99999, 'quiet_hours_credited_at' => '2026-10-25 12:30:00']);
        $this->assertSame(20, $booking->quiet_hours_points);
        $this->assertNull($booking->quiet_hours_credited_at);
        $booking->update(['quiet_hours_points' => 99999, 'status' => 'completed']);
        $this->assertSame(20, (int) LoyaltyPoint::first()->points);
    }

    public function test_failed_ledger_write_rolls_back_credit_and_can_be_retried(): void
    {
        $booking = $this->booking();
        LoyaltyPointTransaction::creating(fn () => throw new \RuntimeException('Simulated ledger failure'));
        try { $booking->update(['status' => 'completed']); $this->fail('Expected failure'); }
        catch (\RuntimeException $e) { $this->assertSame('Simulated ledger failure', $e->getMessage()); }
        $this->assertSame(0, LoyaltyPoint::count());
        $this->assertNull($booking->fresh()->quiet_hours_credited_at);
        LoyaltyPointTransaction::flushEventListeners();
        $booking->refresh()->update(['note' => 'Retry']);
        $this->assertSame(20, (int) LoyaltyPoint::first()->points);
        $this->assertSame(1, LoyaltyPointTransaction::count());
    }

    public function test_admin_can_select_the_same_hour_on_different_days_and_disable_rewards(): void
    {
        $controller = new \App\Http\Controllers\Backend\QuietHoursController();
        $request = \Illuminate\Http\Request::create('/app/quiet-hours', 'POST', [
            'branch_id' => 1, 'enabled' => '1', 'points' => '35', 'hours' => [0 => ['12', '12'], 1 => ['12']],
        ]);
        $this->assertSame(302, $controller->store($request)->getStatusCode());
        $row = BranchQuietHours::where('branch_id', 1)->first();
        $this->assertSame([12], $row->hours[0]);
        $this->assertSame([12], $row->hours[1]);
        $this->assertSame(35, $row->points);
        $controller->store(\Illuminate\Http\Request::create('/app/quiet-hours', 'POST', [
            'branch_id' => 1, 'enabled' => '0', 'points' => 35, 'hours' => [],
        ]));
        $this->assertFalse($row->fresh()->enabled);
        $this->assertSame(0, $this->booking()->quiet_hours_points);
    }

    public function test_enabled_settings_require_valid_hours_and_positive_points(): void
    {
        $controller = new \App\Http\Controllers\Backend\QuietHoursController();
        foreach ([['points' => 0, 'hours' => [[12]]], ['hours' => [[24]]], ['hours' => []]] as $bad) {
            try {
                $controller->store(\Illuminate\Http\Request::create('/app/quiet-hours', 'POST',
                    array_merge(['branch_id' => 1, 'enabled' => 1, 'points' => 20], $bad)));
                $this->fail('Invalid quiet hours accepted');
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->assertNotEmpty($e->errors());
            }
        }
        $this->assertSame(20, BranchQuietHours::first()->points);
    }

    public function test_public_api_exposes_only_enabled_settings_and_branch_timezone(): void
    {
        BranchQuietHours::where('branch_id', 1)->update(['enabled' => false]);
        BranchQuietHours::create(['branch_id' => 2, 'enabled' => true, 'points' => 40, 'hours' => [[12]]]);
        $response = (new \App\Http\Controllers\Api\QuietHoursController())();
        $data = $response->getData(true);
        $this->assertCount(1, $data['data']);
        $this->assertSame(2, $data['data'][0]['branch_id']);
        $this->assertSame(40, $data['data'][0]['points']);
        $this->assertSame('Asia/Riyadh', $data['timezone']);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_missing_migration_keeps_bookings_and_public_api_available(): void
    {
        \Illuminate\Support\Facades\Schema::drop('branch_quiet_hours');
        $booking = $this->booking();
        $this->assertTrue($booking->exists);
        $this->assertFalse(app(\App\Services\QuietHoursRewardService::class)->available());
        $response = (new \App\Http\Controllers\Api\QuietHoursController())();
        $this->assertSame([], $response->getData(true)['data']);
    }

    public function test_dashboard_routes_enforce_existing_loyalty_permissions(): void
    {
        $routes = app('router')->getRoutes();
        $read = $routes->getByName('backend.quiet-hours.index');
        $write = $routes->getByName('backend.quiet-hours.store');
        $this->assertContains('auth', $read->gatherMiddleware());
        $this->assertContains('permission:view_loyalty', $read->gatherMiddleware());
        $this->assertContains('auth', $write->gatherMiddleware());
        $this->assertContains('permission:store_loyalty', $write->gatherMiddleware());
    }

    public function test_settings_template_compiles_without_php_syntax_errors(): void
    {
        $compiled = app('blade.compiler')->compileString(file_get_contents(resource_path('views/backend/quiet-hours/index.blade.php')));
        $this->assertNotEmpty(token_get_all($compiled, TOKEN_PARSE));
    }
}
