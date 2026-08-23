@extends('layouts.app')

@section('content')
<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="eyebrow">{{ __('common.administration') }}</p>
        <h1 class="page-title">{{ __('common.users') }}</h1>
    </div>
    @can('user.create')
        <a class="btn-primary" href="{{ route('users.create') }}">{{ __('common.new_user') }}</a>
    @endcan
</div>

<form method="GET" action="{{ route('users.index') }}" class="card mb-6">
    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label class="form-label">{{ __('common.search_users') }}</label>
            <input class="form-input" type="search" name="search" value="{{ $search }}" placeholder="{{ __('common.search_users_hint') }}">
        </div>
        <div>
            <label class="form-label">{{ __('common.role') }}</label>
            <select class="form-input" name="role">
                <option value="">{{ __('common.all') }}</option>
                @foreach($roles as $role)
                    <option value="{{ $role->name }}" @selected($selectedRole === $role->name)>{{ $role->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="mt-5 flex flex-wrap gap-3">
        <button class="btn-primary">{{ __('common.filter') }}</button>
        <a class="btn-secondary" href="{{ route('users.index') }}">{{ __('common.reset') }}</a>
    </div>
</form>

<div class="card overflow-hidden p-0">
    <table class="data-table">
        <thead>
            <tr>
                <th>{{ __('common.name') }}</th>
                <th>{{ __('common.email') }}</th>
                <th>{{ __('common.roles') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                <tr>
                    <td class="font-semibold">{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->roles->pluck('name')->join(', ') }}</td>
                    <td class="text-end">
                        @can('user.update')
                            <a class="btn-small" href="{{ route('users.edit', $user) }}">{{ __('common.edit') }}</a>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="empty">{{ __('common.no_data') }}</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="p-4">{{ $users->links() }}</div>
</div>
@endsection
