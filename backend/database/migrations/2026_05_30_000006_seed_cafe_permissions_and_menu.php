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
            'view_cafe',
            'manage_cafe_categories',
            'manage_cafe_items',
            'manage_cafe_tables',
            'view_cafe_orders',
            'update_cafe_orders',
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

        $parentId = DB::table('menubuilders')->where('nickname', 'cafe')->where('menu_type', 'vertical')->value('id');
        if (! $parentId) {
            $parentId = DB::table('menubuilders')->insertGetId([
                'menu_type' => 'vertical',
                'menu_item_type' => 'parent',
                'title' => 'cafe.management',
                'nickname' => 'cafe',
                'short_title' => 'C',
                'is_route' => 1,
                'active' => json_encode(['app/cafe*']),
                'order' => 8,
                'menu_level' => 0,
                'start_icon' => 'fa-solid fa-mug-hot',
                'permission' => json_encode(['view_cafe']),
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $children = [
            ['title' => 'cafe.categories', 'route' => 'backend.cafe.categories.index', 'active' => ['app/cafe/categories*'], 'permission' => ['manage_cafe_categories'], 'order' => 0],
            ['title' => 'cafe.items', 'route' => 'backend.cafe.items.index', 'active' => ['app/cafe/items*'], 'permission' => ['manage_cafe_items'], 'order' => 1],
            ['title' => 'cafe.tables', 'route' => 'backend.cafe.tables.index', 'active' => ['app/cafe/tables*'], 'permission' => ['manage_cafe_tables'], 'order' => 2],
            ['title' => 'cafe.orders', 'route' => 'backend.cafe.orders.index', 'active' => ['app/cafe/orders*'], 'permission' => ['view_cafe_orders'], 'order' => 3],
        ];

        foreach ($children as $child) {
            DB::table('menubuilders')->updateOrInsert(
                ['route' => $child['route'], 'menu_type' => 'vertical'],
                [
                    'menu_item_type' => 'link',
                    'title' => $child['title'],
                    'short_title' => '-',
                    'is_route' => 1,
                    'active' => json_encode($child['active']),
                    'order' => $child['order'],
                    'menu_level' => 1,
                    'parent_id' => $parentId,
                    'permission' => json_encode($child['permission']),
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Cache::forget('menu.builder');
    }

    public function down(): void
    {
        DB::table('menubuilders')->where('nickname', 'cafe')->orWhereIn('route', [
            'backend.cafe.categories.index',
            'backend.cafe.items.index',
            'backend.cafe.tables.index',
            'backend.cafe.orders.index',
        ])->delete();

        $permissionIds = DB::table('permissions')->whereIn('name', [
            'view_cafe',
            'manage_cafe_categories',
            'manage_cafe_items',
            'manage_cafe_tables',
            'view_cafe_orders',
            'update_cafe_orders',
        ])->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Cache::forget('menu.builder');
    }
};
