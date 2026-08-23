<?php
namespace App\Exports;
use App\Models\Complaint;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\{Exportable,FromQuery,WithHeadings,WithMapping};
class ComplaintsExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;
    public function __construct(private array $filters) {}
    public function query() { return Complaint::with(['customer','branch','service','source','category','type','priority','status','creator','resolver'])->filter($this->filters)->latest('complaint_date'); }
    public function headings(): array { return app()->getLocale()==='ar' ? ['رقم الشكوى','اسم العميل','الهاتف الأساسي','الفرع','الخدمة','المصدر','التصنيف','نوع الشكوى','الأولوية','الحالة','تاريخ الشكوى','الوصف','الحل','أنشأها','حلها'] : ['Complaint ID','Customer Name','Primary Phone','Branch','Service','Source','Category','Complaint Type','Priority','Status','Complaint Date','Description','Resolution','Created By','Resolved By']; }
    public function map($complaint): array { return [$complaint->id,$complaint->customer?->name,$complaint->customer?->phone_primary,$complaint->branch?->name,$complaint->service?->name,$complaint->source?->name,$complaint->category?->name,$complaint->type?->name,$complaint->priority?->name,$complaint->status?->name,optional($complaint->complaint_date)->format('Y-m-d'),$complaint->description,$complaint->resolution,$complaint->creator?->name,$complaint->resolver?->name]; }
}
