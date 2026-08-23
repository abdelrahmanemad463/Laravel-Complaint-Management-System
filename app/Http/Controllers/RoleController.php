<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\{Permission,Role};

class RoleController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()?->can('role.view'), 403);
        $roles = Role::withCount('permissions')->orderBy('name')->get();
        return view('roles.index', compact('roles'));
    }

    public function edit(Role $role)
    {
        abort_unless(auth()->user()?->can('role.update'), 403);
        if ($role->name === 'Super Admin' && ! auth()->user()->hasRole('Super Admin')) abort(403);
        $permissions = Permission::orderBy('name')->get();
        return view('roles.form', compact('role','permissions'));
    }

    public function update(Request $request, Role $role)
    {
        abort_unless(auth()->user()?->can('role.update'), 403);
        if ($role->name === 'Super Admin' || $role->name === 'Customer Support' || $role->name === 'Viewer') abort_unless(auth()->user()->hasRole('Super Admin'), 403);
        $values = $request->validate(['permissions' => ['nullable','array'], 'permissions.*' => ['exists:permissions,name']]);
        $role->syncPermissions($values['permissions'] ?? []);
        return redirect()->route('roles.index')->with('success', __('common.saved'));
    }
}
