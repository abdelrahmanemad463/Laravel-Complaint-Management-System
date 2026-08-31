<?php

namespace App\Exports\Visitors;

use App\Models\VisitorChecklistItem;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Exports the current inspection master data from the database in the exact
 * importable format, so an admin can download, edit in Excel, and re-upload.
 */
class VisitorCurrentMasterDataExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;
    use HasMasterDataColumns;

    public function __construct(private array $filters = []) {}

    public function query()
    {
        $q = VisitorChecklistItem::query()
            ->with(['section', 'visitType', 'rootCause'])
            ->orderBy('visit_type_id')
            ->orderBy('sort_order')
            ->orderBy('id');

        if (!empty($this->filters['visit_type_id'])) {
            $q->where('visit_type_id', $this->filters['visit_type_id']);
        }
        if (!empty($this->filters['section_id'])) {
            $q->where('section_id', $this->filters['section_id']);
        }

        return $q;
    }

    public function map($item): array
    {
        return [
            $item->code,
            $item->visitType?->code,
            $item->section?->name,
            $item->title,
            $item->severity,
            $item->rootCause?->name,
            $item->immediate_action,
            $item->corrective_action,
            $item->responsible,
            $item->deadline, // period
            $item->preventive_action,
            $item->deduction_score,
        ];
    }
}
