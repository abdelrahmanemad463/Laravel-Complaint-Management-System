<?php

namespace App\Exports\Visitors;

/**
 * Shared column definition for quality-visits master data Excel files.
 */
trait HasMasterDataColumns
{
    /**
     * Column headings, in the exact required order.
     */
    public function headings(): array
    {
        return [
            'code',
            'inspection_type',
            'section',
            'note',
            'severity',
            'immediate_action',
            'corrective_action',
            'responsible',
            'period',
            'preventive_action',
            'deduction_score',
        ];
    }
}
