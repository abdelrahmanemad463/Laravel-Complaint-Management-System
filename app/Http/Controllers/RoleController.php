<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\{Permission,Role};

class RoleController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()?->can('role.view'), 403);
        $roles = Role::withCount(['permissions', 'users'])->orderBy('name')->get();
        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        abort_unless(auth()->user()?->can('role.create'), 403);
        return view('roles.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()?->can('role.create'), 403);
        $values = $request->validate(['name' => ['required', 'string', 'max:255', 'unique:roles,name', Rule::notIn(['Super Admin', 'Admin', 'Customer Support', 'Viewer'])]]);
        Role::create(['name' => trim($values['name']), 'guard_name' => 'web']);
        return redirect()->route('roles.index')->with('success', __('common.role_created'));
    }

    public function edit(Role $role)
    {
        abort_unless(auth()->user()?->can('role.update'), 403);
        if ($role->name === 'Super Admin' && ! auth()->user()->hasRole('Super Admin')) abort(403);
        $permissions = Permission::orderBy('name')->get();
        return view('roles.form', compact('role','permissions'));
    }

    public function destroy(Role $role)
    {
        abort_unless(auth()->user()?->can('role.delete'), 403);
        if (in_array($role->name, ['Super Admin', 'Admin', 'Customer Support', 'Viewer'], true)) {
            return back()->with('error', __('common.protected_role_cannot_delete'));
        }
        if ($role->users()->exists()) {
            return back()->with('error', __('common.role_cannot_delete_used'));
        }
        $role->delete();
        return redirect()->route('roles.index')->with('success', __('common.role_deleted'));
    }

    public function update(Request $request, Role $role)
    {
        abort_unless(auth()->user()?->can('role.update'), 403);
        if ($role->name === 'Super Admin') abort_unless(auth()->user()->hasRole('Super Admin'), 403);
        $values = $request->validate(['permissions' => ['nullable','array'], 'permissions.*' => ['exists:permissions,name']]);
        $role->syncPermissions($values['permissions'] ?? []);
        return redirect()->route('roles.index')->with('success', __('common.saved'));
    }
}
