@extends('layouts.app')
@section('content')
<div class="mb-8"><p class="eyebrow">{{ __('common.administration') }}</p><h1 class="page-title">{{ __('common.roles') }}</h1><p class="page-subtitle">{{ __('common.roles_subtitle') }}</p></div>
<div class="card overflow-hidden p-0"><table class="data-table"><thead><tr><th>{{ __('common.role') }}</th><th>{{ __('common.permissions') }}</th><th></th></tr></thead><tbody>@foreach($roles as $role)<tr><td class="font-semibold">{{ $role->name }}</td><td>{{ $role->permissions_count }}</td><td class="text-end"><a class="btn-small" href="{{ route('roles.edit',$role) }}">{{ __('common.configure') }}</a></td></tr>@endforeach</tbody></table></div>
@endsection
