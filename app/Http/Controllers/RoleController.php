<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        return view('dashboard.roles.index', [
            'permissions' => Permission::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('roles', 'name')],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::exists('permissions', 'name')],
        ]);

        $role = Role::create(['name' => strtolower($data['name'])]);
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('dashboard.roles')->with('status', 'Role created.');
    }

    public function edit(Role $role)
    {
        return view('dashboard.roles.edit', [
            'role' => $role,
            'permissions' => Permission::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::exists('permissions', 'name')],
        ]);

        if ($role->name === 'super' && strtolower($data['name']) !== 'super') {
            return back()->with('error', 'The super role cannot be renamed.');
        }

        $role->update(['name' => strtolower($data['name'])]);

        // super always keeps every permission
        $role->syncPermissions($role->name === 'super' ? Permission::all() : ($data['permissions'] ?? []));

        return redirect()->route('dashboard.roles')->with('status', 'Role updated.');
    }

    public function destroy(Role $role)
    {
        if ($role->name === 'super') {
            return redirect()->route('dashboard.roles')->with('error', 'The super role cannot be deleted.');
        }

        if ($role->users()->count() > 0) {
            return redirect()->route('dashboard.roles')->with('error', 'This role is assigned to users — reassign them first.');
        }

        $role->delete();

        return redirect()->route('dashboard.roles')->with('status', 'Role deleted.');
    }

    public function storePermission(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('permissions', 'name')],
        ]);

        Permission::create(['name' => strtolower($data['name'])]);

        // super automatically gets any new permission
        Role::findByName('super')->givePermissionTo(strtolower($data['name']));

        return redirect()->route('dashboard.roles')->with('status', 'Permission created.');
    }

    public function destroyPermission(Permission $permission)
    {
        $permission->delete();

        return redirect()->route('dashboard.roles')->with('status', 'Permission deleted.');
    }
}
