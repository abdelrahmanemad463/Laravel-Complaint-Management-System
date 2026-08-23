@extends('layouts.app')
@section('content')
<div class="mb-8 flex items-end justify-between"><div><p class="eyebrow">{{ __('common.administration') }}</p><h1 class="page-title">{{ __('common.users') }}</h1></div><a class="btn-primary" href="{{ route('users.create') }}">{{ __('common.new_user') }}</a></div>
<div class="card overflow-hidden p-0"><table class="data-table"><thead><tr><th>{{ __('common.name') }}</th><th>{{ __('common.email') }}</th><th>{{ __('common.roles') }}</th><th></th></tr></thead><tbody>@forelse($users as $user)<tr><td class="font-semibold">{{ $user->name }}</td><td>{{ $user->email }}</td><td>{{ $user->roles->pluck('name')->join(', ') }}</td><td class="text-end"><a class="btn-small" href="{{ route('users.edit',$user) }}">{{ __('common.edit') }}</a></td></tr>@empty<tr><td colspan="4" class="empty">{{ __('common.no_data') }}</td></tr>@endforelse</tbody></table><div class="p-4">{{ $users->links() }}</div></div>
@endsection
