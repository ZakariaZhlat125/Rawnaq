<?php
namespace Tests\Feature;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
class BranchAvailabilityTest extends TestCase
{
    public function test_inactive_and_missing_branches_are_rejected_before_booking_is_created(): void
    {
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Schema::create('branches', function (Blueprint $t) { $t->id(); $t->boolean('status'); $t->softDeletes(); });
        DB::table('branches')->insert(['id'=>7,'status'=>0]);
        foreach ([7,99] as $id) {
            try {
                (new \Modules\Booking\Http\Controllers\Backend\API\BookingsController())->store(Request::create('/api/save-booking','POST',['branch_id'=>$id]));
                $this->fail('Unavailable branch accepted');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('branch_id',$e->errors());
            }
        }
    }
}
