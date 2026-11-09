<?php
namespace App\Services;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
class EmployeeAccess {
    public const PERMISSIONS = ['view_own_employee_bookings','view_own_employee_reports','view_notification'];
    public static function limited(?User $user): bool {
        return $user && $user->hasRole('employee') && !$user->hasAnyRole(['admin','manager']);
    }
    public static function syncPermissions(): void {
        $role=Role::firstOrCreate(['name'=>'employee','guard_name'=>'web'],['title'=>'employee','is_fixed'=>true]);
        foreach(self::PERMISSIONS as $name) Permission::firstOrCreate(['name'=>$name,'guard_name'=>'web']);
        $role->syncPermissions(self::PERMISSIONS);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
