@extends('layouts.app')
@section('content')
<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        <a href="{{ route('visitors.master-data') }}" class="back-link">← {{ __('visitors.master') }}</a>
        <h1 class="page-title mt-4">{{ __('visitors.master_import_preview') }}</h1>
        <p class="page-subtitle">{{ __('visitors.master_import_ready') }}</p>
    </div>
</div>

@if(session('error'))
<div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ session('error') }}</div>
@endif

{{-- Summary --}}
<div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
    <div class="card"><div class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('visitors.master_file') }}</div><div class="mt-1 break-all text-sm font-bold text-slate-900">{{ $import->file_name }}</div></div>
    <div class="card"><div class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('visitors.master_file_size') }}</div><div class="mt-1 text-lg font-bold text-slate-900">{{ number_format($import->file_size / 1048576, 2) }} MB</div></div>
    <div class="card"><div class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('visitors.master_rows') }}</div><div class="mt-1 text-lg font-bold text-slate-900">{{ $import->total_rows }}</div></div>
    <div class="card"><div class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('visitors.master_valid') }} / {{ __('visitors.master_invalid') }}</div><div class="mt-1 text-lg font-bold"><span class="text-emerald-700">{{ $validRows->count() }}</span> / <span class="text-rose-600">{{ $invalidRows->count() }}</span></div></div>
</div>

{{-- Invalid rows (block confirm) --}}
@if($invalidRows->count())
<div class="card mb-6 border-rose-200">
    <h2 class="mb-1 flex items-center gap-2 text-lg font-bold text-rose-700">
        <span>⚠</span> {{ __('visitors.master_validation_errors') }}
    </h2>
    <p class="mb-3 text-sm text-rose-600">{{ __('visitors.master_invalid_rows_found', ['count' => $invalidRows->count()]) }}</p>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-rose-200 bg-rose-50 text-left text-xs font-bold uppercase tracking-wide text-rose-600">
                    <th class="px-4 py-3">{{ __('visitors.master_row') }}</th>
                    <th class="px-4 py-3">{{ __('visitors.master_error') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invalidRows as $row)
                <tr class="border-b border-rose-100">
                    <td class="px-4 py-3 font-semibold text-slate-900">{{ $row->row_number }}</td>
                    <td class="px-4 py-3 text-rose-700">{{ implode('; ', $row->errorsList()) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        <form method="POST" action="{{ route('visitors.master-data.import.cancel', $import) }}" class="inline">
            @csrf
            <button class="btn-secondary">{{ __('visitors.master_cancel_import') }}</button>
        </form>
    </div>
</div>
@endif

{{-- Valid rows preview --}}
@if($validRows->count())
<div class="card mb-6">
    <h2 class="mb-3 text-lg font-bold text-slate-900">{{ __('visitors.master_confirm_heading') }}</h2>
    <p class="mb-4 text-sm text-slate-500">{{ __('visitors.master_confirm_message', ['count' => $validRows->count()]) }}</p>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">{{ __('visitors.master_code') }}</th>
                    <th class="px-4 py-3">{{ __('visitors.master_inspection_type') }}</th>
                    <th class="px-4 py-3">{{ __('visitors.section') }}</th>
                    <th class="px-4 py-3">{{ __('visitors.master_note') }}</th>
                    <th class="px-4 py-3">{{ __('visitors.master_severity') }}</th>
                    <th class="px-4 py-3">{{ __('visitors.master_deduction_score') }}</th>
                    <th class="px-4 py-3">{{ __('visitors.period') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($validRows as $row)
                @php $data = $row->dataArray(); @endphp
                <tr class="border-b border-slate-100 hover:bg-slate-50">
                    <td class="px-4 py-3 font-semibold text-slate-900">{{ $data['code'] }}</td>
                    <td class="px-4 py-3">{{ $data['inspection_type'] }}</td>
                    <td class="px-4 py-3">{{ $data['section'] }}</td>
                    <td class="px-4 py-3">{{ $data['note'] }}</td>
                    <td class="px-4 py-3 font-semibold capitalize">{{ $data['severity'] }}</td>
                    <td class="px-4 py-3 font-semibold">{{ $data['deduction_score'] }}</td>
                    <td class="px-4 py-3">{{ $data['period_hours'] !== null && $data['period_hours'] !== '' ? \App\Services\Visitors\DueDateService::hoursLabel($data['period_hours']) : '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="flex flex-wrap items-center gap-3">
    <form method="POST" action="{{ route('visitors.master-data.import.confirm', $import) }}" class="inline">
        @csrf
        <button class="btn-primary">{{ __('visitors.master_confirm') }}</button>
    </form>
    <form method="POST" action="{{ route('visitors.master-data.import.cancel', $import) }}" class="inline">
        @csrf
        <button class="btn-secondary">{{ __('visitors.master_cancel_import') }}</button>
    </form>
    <a href="{{ route('visitors.master-data.import.preview', $import) }}" class="hidden"></a>
</div>
@else
<div class="card text-center text-slate-500">{{ __('visitors.master_no_valid_rows') }}</div>
@endif
@endsection
