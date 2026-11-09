<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')->updateOrInsert(
            ['name' => 'manage_center_info', 'guard_name' => 'web'],
            ['is_fixed' => 0, 'created_at' => now(), 'updated_at' => now()]
        );

        $adminRole = DB::table('roles')->where('name', 'admin')->first();
        $permissionId = DB::table('permissions')->where('name', 'manage_center_info')->value('id');

        if ($adminRole && $permissionId) {
            DB::table('role_has_permissions')->updateOrInsert([
                'permission_id' => $permissionId,
                'role_id' => $adminRole->id,
            ]);
        }

        DB::table('menubuilders')->updateOrInsert(
            ['route' => 'backend.center-info.index', 'menu_type' => 'vertical'],
            [
                'menu_item_type' => 'link',
                'title' => 'center_info.menu_title',
                'short_title' => 'I',
                'is_route' => 1,
                'active' => json_encode(['app/center-info*']),
                'order' => 9,
                'menu_level' => 0,
                'start_icon' => 'fa-solid fa-qrcode',
                'permission' => json_encode(['manage_center_info']),
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Cache::forget('menu.builder');
    }

    public function down(): void
    {
        DB::table('menubuilders')->where('route', 'backend.center-info.index')->delete();

        $permissionId = DB::table('permissions')->where('name', 'manage_center_info')->value('id');
        if ($permissionId) {
            DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Cache::forget('menu.builder');
    }
};
