<?php
namespace Tests\Feature;
use App\Models\User;
use App\Services\EmployeeAccess;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
class EmployeeOnlyAccessTest extends TestCase {
    private User $employee;
    protected function setUp(): void {
        parent::setUp();
        $this->assertSame('sqlite',DB::connection()->getDriverName());$this->assertSame(':memory:',DB::connection()->getDatabaseName());
        config(['app.key'=>'base64:'.base64_encode(str_repeat('t',32))]);
        (require base_path('database/migrations/2023_01_01_000021_create_permission_tables.php'))->up();
        Schema::create('users',function(Blueprint $t){$t->id();$t->string('first_name');$t->string('last_name')->default('');$t->boolean('status')->default(true);$t->boolean('is_banned')->default(false);$t->softDeletes();});
        DB::table('users')->insert([['id'=>1,'first_name'=>'Employee'],['id'=>2,'first_name'=>'Colleague'],['id'=>3,'first_name'=>'Customer']]);
        Schema::create('branches',function(Blueprint $t){$t->id();$t->softDeletes();});
        Schema::create('settings',function(Blueprint $t){$t->id();$t->string('name');$t->text('val')->nullable();$t->string('type')->default('string');$t->softDeletes();});
        Schema::create('branch_employee',function(Blueprint $t){$t->id();$t->integer('employee_id');$t->integer('branch_id');});
        Schema::create('bookings',function(Blueprint $t){$t->id();$t->integer('user_id');$t->string('status');$t->dateTime('start_date_time');$t->softDeletes();});
        Schema::create('services',function(Blueprint $t){$t->id();$t->text('name');});
        Schema::create('packages',function(Blueprint $t){$t->id();$t->text('name');});
        Schema::create('booking_services',function(Blueprint $t){$t->id();$t->integer('booking_id');$t->integer('employee_id');$t->integer('service_id');$t->dateTime('start_date_time')->nullable();$t->integer('duration_min')->default(30);$t->decimal('service_price');$t->decimal('discount_amount')->default(0);});
        Schema::create('booking_packages',function(Blueprint $t){$t->id();$t->integer('booking_id');$t->integer('employee_id');$t->integer('package_id');$t->decimal('package_price');});
        Schema::create('commission_earnings',function(Blueprint $t){$t->id();$t->integer('employee_id');$t->decimal('commission_amount');$t->timestamps();});
        Schema::create('notifications',function(Blueprint $t){$t->uuid('id')->primary();$t->string('type');$t->morphs('notifiable');$t->text('data');$t->timestamp('read_at')->nullable();$t->timestamps();});
        EmployeeAccess::syncPermissions();
        $this->employee=User::find(1);$this->employee->assignRole('employee');
        DB::table('bookings')->insert([
            ['id'=>1,'user_id'=>3,'status'=>'completed','start_date_time'=>'2026-10-20 14:00:00'],
            ['id'=>2,'user_id'=>3,'status'=>'completed','start_date_time'=>'2026-10-20 15:00:00'],
            ['id'=>3,'user_id'=>3,'status'=>'confirmed','start_date_time'=>'2026-10-20 16:00:00'],
        ]);
        DB::table('services')->insert([['id'=>1,'name'=>'{"en":"Mine","ar":"خدمتي"}'],['id'=>2,'name'=>'{"en":"Private colleague service"}']]);
        DB::table('packages')->insert(['id'=>1,'name'=>'{"en":"My package"}']);
        DB::table('booking_services')->insert([
            ['booking_id'=>1,'employee_id'=>1,'service_id'=>1,'service_price'=>100],
            ['booking_id'=>1,'employee_id'=>2,'service_id'=>2,'service_price'=>900],
            ['booking_id'=>2,'employee_id'=>2,'service_id'=>2,'service_price'=>999],
        ]);
        DB::table('booking_packages')->insert(['booking_id'=>3,'employee_id'=>1,'package_id'=>1,'package_price'=>200]);
        DB::table('commission_earnings')->insert([['employee_id'=>1,'commission_amount'=>12,'created_at'=>'2026-10-20'],['employee_id'=>2,'commission_amount'=>9999,'created_at'=>'2026-10-20']]);
    }
    protected function tearDown(): void {
        $p=new \ReflectionProperty(\Illuminate\Database\Eloquent\Model::class,'guardableColumns');$p->setAccessible(true);$p->setValue(null,[]);parent::tearDown();
    }
    public function test_employee_cannot_open_management_pages_or_mutate_bookings_even_with_direct_grants(): void {
        foreach(['view_service','view_customer','edit_booking','setting_payment_method'] as $name){Permission::findOrCreate($name,'web');$this->employee->givePermissionTo($name);$this->assertFalse($this->employee->can($name));}
        $this->actingAs($this->employee);
        foreach(['/app/services','/app/customers','/app/employees',route('backend.branch.index',[],false),'/app/bookings/2','/app/access',route('backend.reports.financial-report',[],false)] as $url)$this->getJson($url)->assertForbidden();
        $this->post('/app/bookings/update-status/2',['status'=>'cancelled'])->assertForbidden();
    }
    public function test_old_booking_entry_redirects_to_the_limited_portal(): void {
        $this->withoutExceptionHandling();
        $this->actingAs($this->employee)->get('/app/bookings')->assertRedirect(route('backend.employee.bookings'));
        $this->get('/admin')->assertRedirect(route('backend.employee.bookings'));
    }
    public function test_api_ignores_spoofed_employee_ids_and_hides_other_lines_of_shared_bookings(): void {
        \Laravel\Sanctum\Sanctum::actingAs($this->employee);
        $response=$this->getJson('/api/employee/bookings?from=2026-10-01&to=2026-10-31&employee_id=2&employee_ids[]=2');
        $response->assertOk()->assertJsonCount(2,'data')->assertJsonPath('data.0.booking_id',1)->assertJsonPath('data.1.booking_id',3);
        $this->assertStringNotContainsString('Private colleague',$response->getContent());
        $this->assertStringNotContainsString('900',$response->getContent());
        $this->getJson('/api/booking-detail?id=2')->assertForbidden();
        $this->getJson('/api/staff?employee_id=2')->assertForbidden();
    }
    public function test_personal_report_contains_only_the_authenticated_employees_values(): void {
        \Laravel\Sanctum\Sanctum::actingAs($this->employee);
        $this->getJson('/api/employee/reports?from=2026-10-01&to=2026-10-31&employee_id=2')->assertOk()
            ->assertJsonPath('data.bookings',2)->assertJsonPath('data.completed',1)->assertJsonPath('data.completed_value',100)->assertJsonPath('data.commission',12);
    }
    public function test_employee_html_portal_does_not_render_management_links_or_colleagues(): void {
        $this->withoutExceptionHandling();
        $this->actingAs($this->employee)->get('/app/employee/bookings?from=2026-10-01&to=2026-10-31')->assertOk()->assertDontSee('Private colleague')->assertDontSee('/app/services',false)->assertSee('حجوزاتي');
    }
    public function test_migration_removes_old_role_permissions_and_preserves_admin_manager_access(): void {
        $role=Role::findByName('employee','web');Permission::findOrCreate('view_customer','web');$role->givePermissionTo('view_customer');
        (require base_path('database/migrations/2026_10_24_000003_restrict_employee_permissions.php'))->up();
        $this->assertEqualsCanonicalizing(EmployeeAccess::PERMISSIONS,$role->fresh()->permissions->pluck('name')->all());
        Role::firstOrCreate(['name'=>'manager','guard_name'=>'web'],['title'=>'manager']);$this->employee->assignRole('manager');$this->assertFalse(EmployeeAccess::limited($this->employee));
        Role::firstOrCreate(['name'=>'admin','guard_name'=>'web'],['title'=>'admin']);$this->employee->assignRole('admin');$this->assertTrue($this->employee->can('setting_payment_method'));
    }
}
