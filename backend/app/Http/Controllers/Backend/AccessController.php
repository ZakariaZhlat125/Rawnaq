<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AccessController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    public function index(Request $request)
    {
        $request->validate(['q' => 'nullable|string|max:100']);
        $users = User::with('roles')->when($request->filled('q'), function ($query) use ($request) {
            $query->where(function ($query) use ($request) {
                foreach (['first_name', 'last_name', 'email', 'mobile'] as $field) {
                    $query->orWhere($field, 'like', '%'.$request->q.'%');
                }
            });
        })->latest('id')->paginate(20)->withQueryString();
        $roles = Role::where('guard_name', 'web')->with('permissions')->withCount('users')->get();
        $permissions = Permission::where('guard_name', 'web')->orderBy('name')->get();
        $editingUser = $request->filled('member') ? User::with('roles')->findOrFail($request->member) : null;
        $editingRole = $request->filled('role') ? Role::where('guard_name', 'web')->with('permissions')->findOrFail($request->role) : null;

        return view('backend.access.index', compact('users', 'roles', 'permissions', 'editingUser', 'editingRole'));
    }

    public function saveMember(Request $request, ?User $member = null)
    {
        // Store the same local Saudi number format used by OTP login.
        $request->validate(['mobile' => 'required|string|max:20']);
        $mobile = preg_replace('/[^0-9]/', '', $request->input('mobile'));
        if (str_starts_with($mobile, '966')) {
            $mobile = '0'.substr($mobile, 3);
        }
        if (preg_match('/^5[0-9]{8}$/', $mobile)) {
            $mobile = '0'.$mobile;
        }
        $request->merge(['mobile' => $mobile]);
        $data = $request->validate([
            'first_name' => 'required|string|max:191', 'last_name' => 'required|string|max:191',
            'email' => ['required', 'email', 'max:191', Rule::unique('users')->ignore($member?->id)],
            'mobile' => ['required', 'string', 'regex:/^05[0-9]{8}$/', Rule::unique('users')->ignore($member?->id)],
            'status' => 'required|boolean', 'roles' => 'required|array|min:1',
            'roles.*' => ['required', 'integer', 'distinct', Rule::exists('roles', 'id')->where('guard_name', 'web')],
        ]);
        DB::transaction(function () use ($request, $data, $member) {
            // Serialize administrator changes to prevent concurrent removal of the last administrator.
            Role::where('name', 'admin')->where('guard_name', 'web')->lockForUpdate()->firstOrFail();
            $roles = Role::where('guard_name', 'web')->whereIn('id', $data['roles'])->lockForUpdate()->get();
            abort_if($roles->count() !== count($data['roles']), 422, 'تغيّرت الأدوار. أعد تحميل الصفحة.');
            if ($member) {
                $member = User::lockForUpdate()->findOrFail($member->id);
                abort_if($member->id === $request->user()->id && (!$data['status'] || !$roles->contains('name', 'admin')), 422, 'لا يمكنك تعطيل حسابك أو إزالة دور المدير منه.');
                abort_if($member->hasRole('admin') && (!$data['status'] || !$roles->contains('name', 'admin')) && User::role('admin')->where('status', 1)->where('id', '!=', $member->id)->count() === 0, 422, 'يجب الإبقاء على مدير نشط واحد على الأقل.');
            }
            $attributes = collect($data)->except(['roles'])->all();
            $member = $member ?: new User;
            $member->fill($attributes)->save();
            $member->syncRoles($roles);
        });
        return redirect()->route('backend.access.index')->with('success', 'تم حفظ العضوية بنجاح.');
    }

    public function saveRole(Request $request, ?Role $role = null)
    {
        abort_if($role && ($role->is_fixed || in_array($role->name, ['admin', 'user', 'employee', 'manager']) || $role->guard_name !== 'web'), 403, 'هذا الدور أساسي ومحمي.');
        $data = $request->validate([
            'name' => ['required', 'regex:/^[a-z][a-z0-9_]{2,49}$/', Rule::notIn(['admin', 'user', 'employee', 'manager']), Rule::unique('roles')->where('guard_name', 'web')->ignore($role?->id)],
            'title' => 'required|string|max:100', 'permissions' => 'sometimes|array',
            'permissions.*' => ['integer', 'distinct', Rule::exists('permissions', 'id')->where('guard_name', 'web')],
        ]);
        DB::transaction(function () use ($data, $role) {
            $role = $role ?: new Role;
            $role->fill(['name' => $data['name'], 'title' => $data['title'], 'guard_name' => 'web'])->save();
            $role->syncPermissions(Permission::whereIn('id', $data['permissions'] ?? [])->get());
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        return redirect()->route('backend.access.index')->with('success', 'تم حفظ الدور والصلاحيات.');
    }

    public function deleteRole(Role $role)
    {
        DB::transaction(function () use ($role) {
            $role = Role::lockForUpdate()->findOrFail($role->id);
            abort_if($role->is_fixed || in_array($role->name, ['admin', 'user', 'employee', 'manager']) || $role->users()->exists(), 422, 'لا يمكن حذف دور أساسي أو مرتبط بعضويات.');
            $role->delete();
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        return back()->with('success', 'تم حذف الدور.');
    }
}
