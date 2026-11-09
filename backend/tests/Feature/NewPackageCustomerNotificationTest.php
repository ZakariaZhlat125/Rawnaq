<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\NewPackageCustomerNotification;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Package\Http\Controllers\Backend\PackagesController;
use Modules\Package\Models\Package;
use Tests\TestCase;

class NewPackageCustomerNotificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->boolean('status')->default(1); $table->boolean('is_banned')->default(0);
            $table->softDeletes(); $table->timestamps();
        });
        Schema::create('roles', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('guard_name'); $table->timestamps();
        });
        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id'); $table->string('model_type'); $table->unsignedBigInteger('model_id');
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary(); $table->string('type'); $table->morphs('notifiable');
            $table->text('data'); $table->timestamp('read_at')->nullable(); $table->timestamps();
        });
        (require base_path('Modules/Package/database/migrations/2023_12_08_123647_create_packages_table.php'))->up();
        Schema::table('packages', fn (Blueprint $table) => $table->string('type')->default('package'));
        Schema::create('package_employees', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('package_id'); $table->unsignedBigInteger('employee_id');
        });
        Schema::create('package_services', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('package_id'); $table->unsignedBigInteger('service_id');
            $table->integer('qty'); $table->decimal('service_price'); $table->string('service_name');
            $table->decimal('discounted_price'); $table->timestamps();
        });
        Schema::create('branches', fn (Blueprint $table) => $table->id());
        Schema::create('services', function (Blueprint $table) {
            $table->id(); $table->text('name'); $table->boolean('status')->default(1); $table->softDeletes();
        });
        Schema::create('service_branches', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('service_id'); $table->unsignedBigInteger('branch_id'); $table->decimal('service_price');
        });
        DB::table('branches')->insert(['id' => 1]);
        DB::table('services')->insert(['id' => 9, 'name' => json_encode(['ar' => 'خدمة', 'en' => 'Service']), 'status' => 1]);
        DB::table('service_branches')->insert(['service_id' => 9, 'branch_id' => 1, 'service_price' => 100]);
        DB::table('roles')->insert([['id'=>1, 'name'=>'user', 'guard_name'=>'web'], ['id'=>2, 'name'=>'admin', 'guard_name'=>'web']]);
        foreach ([1,2,3,4,5] as $id) {
            DB::table('users')->insert(['id'=>$id, 'status'=>$id === 3 ? 0 : 1,
                'is_banned'=>$id === 4 ? 1 : 0, 'deleted_at'=>$id === 5 ? now() : null]);
            DB::table('model_has_roles')->insert(['role_id'=>$id === 2 ? 2 : 1, 'model_type'=>User::class, 'model_id'=>$id]);
        }
    }

    private function package(): Package
    {
        return Package::create(['name'=>['ar'=>'باقة العناية', 'en'=>'Care package'],
            'description'=>['ar'=>'<b>عناية متكاملة</b>', 'en'=>'Complete care'], 'branch_id'=>1, 'package_price'=>100]);
    }

    public function test_arabic_notification_reaches_only_active_customers_even_in_english_locale(): void
    {
        app()->setLocale('en');
        $package = $this->package();
        (new NewPackageCustomerNotification)->send($package);
        $entry = User::find(1)->notifications()->first();
        $this->assertSame('باقة جديدة من رونق', $entry->data['subject']);
        $this->assertSame('تمت إضافة باقة جديدة: باقة العناية — عناية متكاملة', $entry->data['data']['message']);
        $this->assertSame($package->id, $entry->data['data']['id']);
        $this->assertSame(1, DB::table('notifications')->count());
    }

    public function test_controller_creates_notifications_and_later_edits_do_not_repeat_them(): void
    {
        $request = Request::create('/app/package', 'POST', [
            'name'=>json_encode(['ar'=>'باقة العناية', 'en'=>'Care package']), 'description'=>'عناية متكاملة',
            'branch_id'=>1, 'status'=>1, 'services'=>json_encode([[
                'service_id'=>9, 'qty'=>1, 'service_price'=>100, 'discounted_price'=>80,
                'service_name'=>'خدمة', 'totalPrice'=>80,
            ]]),
        ]);
        $response = (new PackagesController)->store($request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, DB::table('package_services')->count());
        $this->assertSame(1, DB::table('notifications')->count());
        Package::first()->update(['description'=>['ar'=>'وصف محدث']]);
        $this->assertSame(1, DB::table('notifications')->count());
    }

    public function test_package_and_notifications_rollback_together(): void
    {
        DB::beginTransaction();
        (new NewPackageCustomerNotification)->send($this->package());
        $this->assertSame(1, DB::table('notifications')->count());
        DB::rollBack();
        $this->assertSame(0, Package::count());
        $this->assertSame(0, DB::table('notifications')->count());
    }

    public function test_undiscounted_package_is_rejected_before_any_records_are_written(): void
    {
        $request = Request::create('/app/package', 'POST', [
            'branch_id' => 1, 'services' => json_encode([[
                'service_id' => 9, 'qty' => 1, 'totalPrice' => 100,
                'service_price' => 9999,
            ]]),
        ]);
        try {
            (new PackagesController)->store($request);
            $this->fail('An undiscounted package was accepted');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('services', $e->errors());
        }
        $this->assertSame(0, Package::count());
        $this->assertSame(0, DB::table('package_services')->count());
        $this->assertSame(0, DB::table('notifications')->count());
    }
}
