<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()?->can('user.view'), 403);
        $search = trim((string) $request->input('search'));
        $selectedRole = (string) $request->input('role');
        $roles = Role::orderBy('name')->get();
        $users = User::with('roles')
            ->when($search !== '', fn ($query) => $query->where(fn ($subQuery) => $subQuery->where('name', 'like', "%$search%")->orWhere('email', 'like', "%$search%")))
            ->when($selectedRole !== '', fn ($query) => $query->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', $selectedRole)))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();
        return view('users.index', compact('users', 'roles', 'search', 'selectedRole'));
    }

    public function create()
    {
        abort_unless(auth()->user()?->can('user.create'), 403);
        $roles = Role::orderBy('name')->get();
        return view('users.form', compact('roles'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()?->can('user.create'), 403);
        $values = $request->validate(['name' => ['required','string','max:255'], 'email' => ['required','email','max:255','unique:users,email'], 'password' => ['required','string','min:8'], 'role' => ['required','exists:roles,name'], 'default_home' => ['nullable','in:dashboard,visitors.dashboard,complaints,visitors']]);
        if ($values['role'] === 'Super Admin' && ! auth()->user()->hasRole('Super Admin')) abort(403);
        $user = User::create(['name' => $values['name'], 'email' => $values['email'], 'password' => $values['password'], 'default_home' => $values['default_home'] ?? null]);
        $user->assignRole($values['role']);
        return redirect()->route('users.index')->with('success', __('common.saved'));
    }

    public function edit(User $user)
    {
        abort_unless(auth()->user()?->can('user.update'), 403);
        if ($user->hasRole('Super Admin') && ! auth()->user()->hasRole('Super Admin')) abort(403);
        $roles = Role::orderBy('name')->get();
        return view('users.form', compact('user','roles'));
    }

    public function update(Request $request, User $user)
    {
        abort_unless(auth()->user()?->can('user.update'), 403);
        if ($user->hasRole('Super Admin') && ! auth()->user()->hasRole('Super Admin')) abort(403);
        $values = $request->validate(['name' => ['required','string','max:255'], 'email' => ['required','email','max:255','unique:users,email,'.$user->id], 'password' => ['nullable','string','min:8'], 'role' => ['required','exists:roles,name'], 'default_home' => ['nullable','in:dashboard,visitors.dashboard,complaints,visitors']]);
        if ($values['role'] === 'Super Admin' && ! auth()->user()->hasRole('Super Admin')) abort(403);
        $payload = ['name' => $values['name'], 'email' => $values['email'], 'default_home' => $values['default_home'] ?? null];
        if (!empty($values['password'])) $payload['password'] = $values['password'];
        $user->update($payload);
        $user->syncRoles([$values['role']]);
        return redirect()->route('users.index')->with('success', __('common.saved'));
    }
}
