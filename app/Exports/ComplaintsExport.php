<?php

namespace App\Exports;

use App\Models\Complaint;
use Illuminate\Support\Facades\Lang;
use Maatwebsite\Excel\Concerns\{Exportable, FromQuery, WithHeadings, WithMapping};

class ComplaintsExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(private array $filters) {}

    public function query()
    {
        return Complaint::with([
            'customer',
            'branch',
            'service',
            'source',
            'category',
            'type',
            'priority',
            'status',
            'creator',
            'resolver',
            'statusHistories.fromStatus',
            'statusHistories.toStatus',
            'statusHistories.changer',
            'activityLogs.user',
        ])->filter($this->filters)->latest('complaint_date');
    }

    public function headings(): array
    {
        return app()->getLocale() === 'ar'
            ? [
                'رقم الشكوى',
                'اسم العميل',
                'الهاتف الأساسي',
                'الفرع',
                'الخدمة',
                'المصدر',
                'التصنيف',
                'نوع الشكوى',
                'الأولوية',
                'الحالة',
                'تاريخ الشكوى',
                'الوصف المختصر',
                'الوصف',
                'الحل',
                'أنشأها',
                'حلها',
                'الخط الزمني',
            ]
            : [
                'Complaint ID',
                'Customer Name',
                'Primary Phone',
                'Branch',
                'Service',
                'Source',
                'Category',
                'Complaint Type',
                'Priority',
                'Status',
                'Complaint Date',
                'Short Description',
                'Description',
                'Resolution',
                'Created By',
                'Resolved By',
                'Timeline',
            ];
    }

    public function map($complaint): array
    {
        return [
            $complaint->id,
            $complaint->customer?->name,
            $complaint->customer?->phone_primary,
            $complaint->branch?->name,
            $complaint->service?->name,
            $complaint->source?->name,
            $complaint->category?->name,
            $complaint->type?->name,
            $complaint->priority?->name,
            $complaint->status?->name,
            optional($complaint->complaint_date)->format('Y-m-d'),
            $complaint->short_description,
            $complaint->description,
            $complaint->resolution,
            $complaint->creator?->name,
            $complaint->resolver?->name,
            $this->timeline($complaint),
        ];
    }

    private function timeline(Complaint $complaint): string
    {
        $events = collect();

        foreach ($complaint->statusHistories as $event) {
            $date = optional($event->changed_at)->format('Y-m-d H:i');
            $actor = $event->changer?->name ?? __('common.system');
            $from = $event->fromStatus?->name ?? __('common.created');
            $to = $event->toStatus?->name ?? '—';
            $line = trim($date.' | '.$actor.': '.$from.' → '.$to);

            if ($event->reason) {
                $line .= ' — '.$event->reason;
            }

            $events->push([
                'timestamp' => $event->changed_at,
                'line' => $line,
            ]);
        }

        foreach ($complaint->activityLogs as $event) {
            $date = optional($event->created_at)->format('Y-m-d H:i');
            $actor = $event->user?->name ?? __('common.system');
            $translationKey = 'activity.'.$event->action;
            $label = Lang::has($translationKey) ? __($translationKey) : __('common.activity_recorded');

            $events->push([
                'timestamp' => $event->created_at,
                'line' => trim($date.' | '.$actor.': '.$label),
            ]);
        }

        return $events
            ->sortBy(fn (array $event) => $event['timestamp']?->getTimestamp() ?? 0)
            ->pluck('line')
            ->implode("\n");
    }
}
