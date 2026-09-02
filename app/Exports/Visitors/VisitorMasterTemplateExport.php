<?php

namespace App\Exports\Visitors;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VisitorMasterTemplateExport implements \Maatwebsite\Excel\Concerns\WithMultipleSheets
{
    use HasMasterDataColumns;

    public function sheets(): array
    {
        return [
            'Template' => new MasterDataTemplateSheet($this->headings(), collect()),
            'Example (Sample Data)' => new MasterDataTemplateSheet($this->headings(), collect($this->exampleRows())),
        ];
    }

    /**
     * Clearly-labelled example rows (sample data, not production).
     */
    protected function exampleRows(): array
    {
        return [
            [
                'DY-001', 'daily', 'استلام الخامات',
                'المواد مستلمة خالية من التلوث', 'Major',
                'إرجاع الشحنة أو إتلاف', 'فحص 100% للموردين', 'إدارة الفرع', '24 ساعة',
                'برنامج تقييم الموردين', 3,
            ],
            [
                'DY-002', 'daily', 'التخزين',
                'مناطق التخزين نظيفة', 'Major',
                'تنظيف فوري', 'جدول تنظيف يومي', 'إدارة الفرع', 'فوري',
                'برنامج تنظيف', 3,
            ],
            [
                'DY-003', 'daily', 'استلام الخامات',
                'الأغذية عالية الخطورة بدرجة حرارة مناسبة', 'Critical',
                'إتلاف المنتج', 'صيانة فورية للثلاجة', 'إدارة الفرع', 'فوري',
                'سجل حرارة مستمر', 5,
            ],
        ];
    }
}
