@extends('layouts.app')
@section('content')
<div class="mb-8"><a class="back-link" href="{{ route('roles.index') }}">← {{ __('common.roles') }}</a><h1 class="page-title mt-4">{{ __('common.configure') }}: {{ $role->name }}</h1>@if($role->name==='Super Admin')<p class="mt-2 text-sm text-amber-700">{{ __('common.super_admin_protected') }}</p>@endif</div>
@php
$grouped = $permissions->groupBy(fn($p) => explode('.', $p->name)[0]);
$order = ['dashboard','customer','complaint','branch','service','source','category','type','priority','status','report','user','role','audit','visit'];
$grouped = $grouped->sortBy(fn($_, $g) => array_search($g, $order) !== false ? array_search($g, $order) : 999);
@endphp
<form method="POST" action="{{ route('roles.update',$role) }}" class="max-w-5xl mx-auto space-y-6">@csrf @method('PUT')
@foreach($grouped as $group => $perms)
<div class="card p-4 sm:p-6">
<h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-slate-700">{{ __('permissions.group_'.$group) }}</h3>
<div class="grid gap-3 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">@foreach($perms as $permission)<label class="flex items-center gap-2 rounded-lg border border-slate-200 p-3 text-sm min-h-[44px]"><input type="checkbox" name="permissions[]" value="{{ $permission->name }}" @checked($role->hasPermissionTo($permission->name)) @disabled($role->name==='Super Admin')><span>{{ __('permissions.'.$permission->name) }}</span></label>@endforeach</div>
</div>
@endforeach
@if($role->name!=='Super Admin')<div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"><a href="{{ route('roles.index') }}" class="btn-secondary min-h-[44px] inline-flex items-center justify-center w-full sm:w-auto">{{ __('common.cancel') }}</a><button class="btn-primary min-h-[44px] w-full sm:w-auto">{{ __('common.save') }}</button></div>@endif</form>
@endsection
