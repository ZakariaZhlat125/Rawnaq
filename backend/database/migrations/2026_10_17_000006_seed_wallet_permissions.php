<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $permissions = [
        'view_wallet_admin',        // wallet dashboard / report
        'manage_wallet_withdrawals', // approve, reject, mark paid
        'adjust_wallet_balance',     // manual credit/debit with a reason
        'manage_wallet_offers',      // top-up offers
        'manage_wallet_policy',      // refund / withdrawal / conversion settings
    ];

    public function up(): void
    {
        foreach ($this->permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission, 'guard_name' => 'web'],
                ['is_fixed' => 0, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        $adminRole = DB::table('roles')->where('name', 'admin')->orWhere('name', 'administrator')->first();
        if ($adminRole) {
            foreach (DB::table('permissions')->whereIn('name', $this->permissions)->pluck('id') as $permissionId) {
                DB::table('role_has_permissions')->updateOrInsert([
                    'permission_id' => $permissionId,
                    'role_id' => $adminRole->id,
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('name', $this->permissions)->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
