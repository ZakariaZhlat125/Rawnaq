<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'view_blog',
            'add_blog',
            'edit_blog',
            'delete_blog',
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission, 'guard_name' => 'web'],
                ['is_fixed' => 0, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        $adminRole = DB::table('roles')->where('name', 'admin')->orWhere('name', 'administrator')->first();
        if ($adminRole) {
            $permissionIds = DB::table('permissions')->whereIn('name', $permissions)->pluck('id');
            foreach ($permissionIds as $permissionId) {
                DB::table('role_has_permissions')->updateOrInsert([
                    'permission_id' => $permissionId,
                    'role_id' => $adminRole->id,
                ]);
            }
        }

        DB::table('menubuilders')->updateOrInsert(
            ['route' => 'backend.blogs.index', 'menu_type' => 'vertical'],
            [
                'menu_item_type' => 'link',
                'title' => 'sidebar.blogs',
                'short_title' => 'B',
                'is_route' => 1,
                'active' => json_encode(['app/blogs*']),
                'order' => 25,
                'menu_level' => 0,
                'start_icon' => 'fa-solid fa-blog',
                'permission' => json_encode(['view_blog']),
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('menubuilders')
            ->where('menu_type', 'vertical')
            ->where('nickname', 'notifications')
            ->update(['order' => 26, 'updated_at' => now()]);

        DB::table('menubuilders')
            ->where('menu_type', 'vertical')
            ->where('route', 'backend.app-banners.index')
            ->update(['order' => 27, 'updated_at' => now()]);

        DB::table('menubuilders')
            ->where('menu_type', 'vertical')
            ->where('route', 'backend.permission-role.list')
            ->update(['order' => 28, 'updated_at' => now()]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Cache::forget('menu.builder');
    }

    public function down(): void
    {
        DB::table('menubuilders')->where('route', 'backend.blogs.index')->delete();

        DB::table('menubuilders')
            ->where('menu_type', 'vertical')
            ->where('nickname', 'notifications')
            ->update(['order' => 25, 'updated_at' => now()]);

        DB::table('menubuilders')
            ->where('menu_type', 'vertical')
            ->where('route', 'backend.app-banners.index')
            ->update(['order' => 26, 'updated_at' => now()]);

        DB::table('menubuilders')
            ->where('menu_type', 'vertical')
            ->where('route', 'backend.permission-role.list')
            ->update(['order' => 27, 'updated_at' => now()]);

        $permissionIds = DB::table('permissions')->whereIn('name', [
            'view_blog',
            'add_blog',
            'edit_blog',
            'delete_blog',
        ])->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Cache::forget('menu.builder');
    }
};
