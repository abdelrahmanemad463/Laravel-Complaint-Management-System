@extends('layouts.app')

@section('content')
<div class="mb-8">
    <p class="eyebrow">{{ __('common.administration') }}</p>
    <h1 class="page-title">{{ __('common.audit_logs') }}</h1>
    <p class="page-subtitle">{{ __('common.audit_logs_subtitle') }}</p>
</div>

<form class="card mb-6 flex flex-wrap gap-3" method="GET">
    <input class="form-input max-w-md flex-1 min-w-[200px]" name="action" value="{{ request('action') }}" placeholder="{{ __('common.action_filter') }}">
    <button class="btn-primary min-h-[44px]">{{ __('common.filter') }}</button>
    <a class="btn-secondary min-h-[44px] inline-flex items-center" href="{{ route('audit-logs.index') }}">{{ __('common.reset') }}</a>
</form>

<div class="card overflow-hidden p-0">
    <div class="overflow-x-auto -mx-3 sm:mx-0">
        <table class="data-table">
        <thead>
            <tr>
                <th>{{ __('common.date') }}</th>
                <th>{{ __('common.user') }}</th>
                <th>{{ __('common.action') }}</th>
                <th>{{ __('common.description') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                @php($activityTranslation = 'activity.' . $log->action)
                <tr>
                    <td>{{ optional($log->created_at)->format('Y-m-d H:i') }}</td>
                    <td>{{ $log->user?->name ?? __('common.system') }}</td>
                    <td class="font-semibold">
                        {{ \Illuminate\Support\Facades\Lang::has($activityTranslation) ? __($activityTranslation) : __('common.activity_recorded') }}
                    </td>
                    <td>
                        {{ \Illuminate\Support\Facades\Lang::has($activityTranslation) ? __($activityTranslation) : __('common.activity_recorded') }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="empty">{{ __('common.no_data') }}</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
    <div class="p-4">{{ $logs->links() }}</div>
</div>
@endsection
