@extends('layouts.app')

@section('content')
@php($editing = isset($record))

<div class="mb-8">
    <a href="{{ route('master.index', $type) }}" class="back-link">← {{ __('common.back') }}</a>
    <h1 class="page-title mt-4">
        {{ $editing ? __('common.edit') : __('common.new') }}
        {{ \Illuminate\Support\Str::singular(__('common.' . $type)) }}
    </h1>
</div>

<form method="POST" action="{{ $editing ? route('master.update', [$type, $record->id]) : route('master.store', $type) }}" class="card max-w-2xl mx-auto p-4 sm:p-6 space-y-5">
    @csrf

    @if($editing)
        @method('PUT')
    @endif

    <div>
        <label class="form-label">{{ __('common.name') }} *</label>
        <input class="form-input" name="name" required value="{{ old('name', $record->name ?? '') }}">
    </div>

    @if(in_array('code', $config['fields'], true))
        <div>
            <label class="form-label">{{ __('common.code') }}</label>
            <input class="form-input" name="code" value="{{ old('code', $record->code ?? '') }}">
        </div>
    @endif

    @if(in_array('color', $config['fields'], true))
        <div>
            <label class="form-label">{{ __('common.color') }}</label>
            <input class="form-input" type="text" name="color" value="{{ old('color', $record->color ?? '#64748b') }}">
        </div>
    @endif

    @if(in_array('level', $config['fields'], true))
        <div>
            <label class="form-label">{{ __('common.level') }}</label>
            <input class="form-input" type="number" name="level" value="{{ old('level', $record->level ?? 0) }}">
        </div>
    @endif

    @if($type === 'types')
        <div>
            <label class="form-label">{{ __('common.category') }} *</label>
            <select class="form-input" name="category_id" required>
                <option value="">{{ __('common.select') }}</option>
                @foreach($categories as $item)
                    <option value="{{ $item->id }}" @selected(old('category_id', $record->category_id ?? '') == $item->id)>{{ $item->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label">{{ __('common.priority') }} *</label>
            <select class="form-input" name="priority_id" required>
                <option value="">{{ __('common.select') }}</option>
                @foreach($priorities as $item)
                    <option value="{{ $item->id }}" @selected(old('priority_id', $record->priority_id ?? '') == $item->id)>{{ $item->name }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <div>
        <label class="form-label">{{ __('common.order') }}</label>
        <input class="form-input" type="number" name="sort_order" value="{{ old('sort_order', $record->sort_order ?? 0) }}">
    </div>

    <label class="flex items-center gap-2">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $record->is_active ?? true))>
        {{ __('common.active') }}
    </label>

    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <a class="btn-secondary min-h-[44px] inline-flex items-center justify-center w-full sm:w-auto" href="{{ route('master.index', $type) }}">{{ __('common.cancel') }}</a>
        <button class="btn-primary min-h-[44px] w-full sm:w-auto">{{ __('common.save') }}</button>
    </div>
</form>
@endsection
