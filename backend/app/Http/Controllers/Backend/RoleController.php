<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleRequest;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin', \App\Http\Middleware\LegacyAccessManagement::class]);
    }

    public function index()
    {
        $data = Role::all();

        if (request()->wantsJson()) {
            return response()->json(['data' => $data, 'status' => true]);
        }
    }

    public function create(Request $request)
    {
        $data = new Role;
        $view = view('permission-role.form-role', ['data' => $data])->render();

        return response()->json(['data' => $view, 'status' => true]);
    }

    public function store(RoleRequest $request)
    {
        $data = $request->all();
        $data = $this->setRoleArray($data);

        $role_data = Role::create($data);

        if (isset($data['import_role'])) {
            $import_role = $data['import_role'];

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            $role = Role::find($import_role);
            $new_role = Role::find($role_data['id']);
            $permissions = $role->permissions;

            if ($new_role) {
                $new_role->permissions()->syncWithoutDetaching($permissions);
            }
        }

        $message = __('messages.create_form', ['form' => __('page.lbl_role')]);

        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $data = Role::find($id);
        $view = view('permission-role.form-role', ['data' => $data])->render();

        return response()->json(['data' => $view, 'status' => true]);
    }

    public function update(RoleRequest $request, $id)
    {
        if (config('sami.is_demo') == true) {
            return redirect()->back()->with('error', __('messages.permission_denied'));
        }

        $role = Role::find($id);
        $data = $request->all();
        $data = $this->setRoleArray($data);

        $role->update($data);

        return redirect()->route('backend.permission-role.list')->withSuccess(__('messages.update_form', ['form' => __('permission-role.role_title')]));
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);

        if (isset($role->id)) {
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            $role->permissions()->detach();
            $role->delete();
            $message = __('messages.delete_form', ['form' => __('page.lbl_role')]);
        }

        return response()->json(['status' => true, 'message' => $message]);
    }

    protected function setRoleArray(array $data): array
    {
        $data['name'] = strtolower(str_replace(' ', '_', preg_replace('/[^a-zA-Z0-9_.]/', '', $data['title'])));
        $data['title'] = str_replace(' ', '_', preg_replace('/[^a-zA-Z0-9_.]/', '', $data['title']));

        return $data;
    }
}
