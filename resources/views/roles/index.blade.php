@extends('layouts.app')

@section('content')
<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="eyebrow">{{ __('common.administration') }}</p>
        <h1 class="page-title">{{ __('common.roles') }}</h1>
        <p class="page-subtitle">{{ __('common.roles_subtitle') }}</p>
    </div>
    @can('role.create')
        <a class="btn-primary" href="{{ route('roles.create') }}">{{ __('common.new_role') }}</a>
    @endcan
</div>

<div class="card overflow-hidden p-0">
    <div class="overflow-x-auto -mx-3 sm:mx-0">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="px-3 sm:px-4 lg:px-6 text-xs sm:text-sm">{{ __('common.role') }}</th>
                    <th class="px-3 sm:px-4 lg:px-6 text-xs sm:text-sm">{{ __('common.permissions') }}</th>
                    <th class="px-3 sm:px-4 lg:px-6 text-xs sm:text-sm">{{ __('common.users') }}</th>
                    <th class="px-3 sm:px-4 lg:px-6"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($roles as $role)
                    <tr>
                        <td class="px-3 sm:px-4 lg:px-6 text-xs sm:text-sm font-semibold">{{ $role->name }}</td>
                        <td class="px-3 sm:px-4 lg:px-6 text-xs sm:text-sm">{{ $role->permissions_count }}</td>
                        <td class="px-3 sm:px-4 lg:px-6 text-xs sm:text-sm">{{ $role->users_count }}</td>
                        <td class="px-3 sm:px-4 lg:px-6 text-end">
                            <div class="flex justify-end gap-2 flex-wrap">
                                @can('role.update')
                                    <a class="btn-small min-h-[36px] inline-flex items-center" href="{{ route('roles.edit', $role) }}">{{ __('common.configure') }}</a>
                                @endcan
                                @can('role.delete')
                                    @if($role->users_count === 0 && !in_array($role->name, ['Super Admin', 'Admin', 'Customer Support', 'Viewer'], true))
                                        <form method="POST" action="{{ route('roles.destroy', $role) }}" onsubmit="return confirm('{{ __('common.confirm_role_delete') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn-small min-h-[36px] text-rose-600 hover:border-rose-300 hover:bg-rose-50 hover:text-rose-700">{{ __('common.delete') }}</button>
                                        </form>
                                    @else
                                        <span class="text-xs text-slate-400" title="{{ $role->users_count > 0 ? __('common.role_cannot_delete_used') : __('common.protected_role_cannot_delete') }}">{{ __('common.delete') }}</span>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty">{{ __('common.no_data') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
