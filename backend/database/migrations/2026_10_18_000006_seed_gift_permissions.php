<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $permissions = [
        'manage_gifts',          // resend, cancel, extend, reveal code, gift settings
        'manage_gift_batches',   // bulk issue + CSV export
        'manage_gift_designs',
        'redeem_gift_branch',    // reception: apply a code to a customer
        'view_gift_reports',
    ];

    public function up(): void
    {
        foreach ($this->permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission, 'guard_name' => 'web'],
                ['is_fixed' => 0, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        $roles = DB::table('roles')->whereIn('name', ['admin', 'administrator', 'manager'])->get(['id', 'name']);
        foreach ($roles as $role) {
            // Managers run the branch: they may redeem codes and see reports, not issue or cancel.
            $names = $role->name === 'manager' ? ['redeem_gift_branch', 'view_gift_reports'] : $this->permissions;
            foreach (DB::table('permissions')->whereIn('name', $names)->pluck('id') as $permissionId) {
                DB::table('role_has_permissions')->updateOrInsert(['permission_id' => $permissionId, 'role_id' => $role->id]);
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
